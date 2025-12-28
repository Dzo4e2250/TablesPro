<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Service;

use DateTime;
use OCA\TablesPro\Db\Comment;
use OCA\TablesPro\Db\CommentMapper;
use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class CommentService {

	public function __construct(
		private CommentMapper $mapper,
		private IUserManager $userManager,
		private PermissionsService $permissionsService,
		private ActivityService $activityService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Get all comments for a row
	 * @return Comment[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId, string $userId): array {
		$comments = $this->mapper->findAllForRow($rowId);
		return $this->enrichComments($comments);
	}

	/**
	 * Get a single comment
	 * @throws NotFoundError
	 */
	public function find(int $id, string $userId): Comment {
		try {
			$comment = $this->mapper->find($id);
			return $this->enrichComment($comment);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$this->logger->error('Comment not found: ' . $e->getMessage());
			throw new NotFoundError('Comment not found');
		}
	}

	/**
	 * Create a new comment
	 * @throws Exception
	 */
	public function create(int $rowId, int $tableId, string $userId, string $message, ?int $replyTo = null): Comment {
		$comment = new Comment();
		$comment->setRowId($rowId);
		$comment->setTableId($tableId);
		$comment->setUserId($userId);
		$comment->setMessage($message);
		$comment->setCreatedAt(new DateTime());
		if ($replyTo !== null) {
			$comment->setReplyTo($replyTo);
		}

		$comment = $this->mapper->insert($comment);

		// Log activity
		$this->activityService->logCommentCreate($tableId, $rowId, $userId, $comment->getId());

		return $this->enrichComment($comment);
	}

	/**
	 * Update a comment
	 * @throws NotFoundError
	 * @throws PermissionError
	 * @throws Exception
	 */
	public function update(int $id, string $message, string $userId): Comment {
		try {
			$comment = $this->mapper->find($id);

			// Only the author can update their comment
			if ($comment->getUserId() !== $userId) {
				throw new PermissionError('Only the author can edit this comment');
			}

			$comment->setMessage($message);
			$comment->setUpdatedAt(new DateTime());

			$comment = $this->mapper->update($comment);
			return $this->enrichComment($comment);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Comment not found');
		}
	}

	/**
	 * Delete a comment
	 * @throws NotFoundError
	 * @throws PermissionError
	 * @throws Exception
	 */
	public function delete(int $id, string $userId, bool $isAdmin = false): void {
		try {
			$comment = $this->mapper->find($id);

			// Only the author or admin can delete
			if ($comment->getUserId() !== $userId && !$isAdmin) {
				throw new PermissionError('Only the author can delete this comment');
			}

			$this->mapper->delete($comment);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Comment not found');
		}
	}

	/**
	 * Count comments for a row
	 */
	public function countForRow(int $rowId): int {
		try {
			return $this->mapper->countForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error counting comments: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Count comments for multiple rows
	 * @param int[] $rowIds
	 * @return array<int, int>
	 */
	public function countForRows(array $rowIds): array {
		try {
			return $this->mapper->countForRows($rowIds);
		} catch (Exception $e) {
			$this->logger->error('Error counting comments: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Delete all comments for a row
	 */
	public function deleteAllForRow(int $rowId): void {
		try {
			$this->mapper->deleteAllForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting comments for row: ' . $e->getMessage());
		}
	}

	/**
	 * Delete all comments for a table
	 */
	public function deleteAllForTable(int $tableId): void {
		try {
			$this->mapper->deleteAllForTable($tableId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting comments for table: ' . $e->getMessage());
		}
	}

	/**
	 * Enrich a comment with user display name
	 */
	private function enrichComment(Comment $comment): Comment {
		$user = $this->userManager->get($comment->getUserId());
		if ($user) {
			$comment->setUserDisplayName($user->getDisplayName());
		}

		// Parse mentions from message
		$mentions = $this->parseMentions($comment->getMessage());
		$comment->setMentions($mentions);

		return $comment;
	}

	/**
	 * Enrich multiple comments
	 * @param Comment[] $comments
	 * @return Comment[]
	 */
	private function enrichComments(array $comments): array {
		return array_map(fn(Comment $c) => $this->enrichComment($c), $comments);
	}

	/**
	 * Parse @mentions from message
	 * @return array<array{userId: string, displayName: string}>
	 */
	private function parseMentions(string $message): array {
		$mentions = [];
		preg_match_all('/@([a-zA-Z0-9_-]+)/', $message, $matches);

		if (!empty($matches[1])) {
			foreach (array_unique($matches[1]) as $userId) {
				$user = $this->userManager->get($userId);
				if ($user) {
					$mentions[] = [
						'userId' => $userId,
						'displayName' => $user->getDisplayName(),
					];
				}
			}
		}

		return $mentions;
	}
}
