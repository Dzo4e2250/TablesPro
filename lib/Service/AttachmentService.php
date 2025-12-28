<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Service;

use DateTime;
use OCA\TablesPro\Db\Attachment;
use OCA\TablesPro\Db\AttachmentMapper;
use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class AttachmentService {

	public function __construct(
		private AttachmentMapper $mapper,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private ActivityService $activityService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Get all attachments for a row
	 * @return Attachment[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId, string $userId): array {
		$attachments = $this->mapper->findAllForRow($rowId);
		return $this->enrichAttachments($attachments, $userId);
	}

	/**
	 * Get a single attachment
	 * @throws NotFoundError
	 */
	public function find(int $id, string $userId): Attachment {
		try {
			$attachment = $this->mapper->find($id);
			return $this->enrichAttachment($attachment, $userId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$this->logger->error('Attachment not found: ' . $e->getMessage());
			throw new NotFoundError('Attachment not found');
		}
	}

	/**
	 * Create a new attachment
	 * @throws Exception
	 */
	public function create(int $rowId, int $tableId, string $userId, int $fileId, string $type = 'file'): Attachment {
		$attachment = new Attachment();
		$attachment->setRowId($rowId);
		$attachment->setTableId($tableId);
		$attachment->setUserId($userId);
		$attachment->setFileId($fileId);
		$attachment->setType($type);
		$attachment->setCreatedAt(new DateTime());

		$attachment = $this->mapper->insert($attachment);
		$enriched = $this->enrichAttachment($attachment, $userId);

		// Log activity
		$this->activityService->logAttachmentCreate($tableId, $rowId, $userId, $attachment->getId(), [
			'fileName' => $enriched->jsonSerialize()['fileName'] ?? 'File',
		]);

		return $enriched;
	}

	/**
	 * Soft delete an attachment
	 * @throws NotFoundError
	 * @throws PermissionError
	 * @throws Exception
	 */
	public function delete(int $id, string $userId, bool $isAdmin = false): void {
		try {
			$attachment = $this->mapper->find($id);

			// Only the author or admin can delete
			if ($attachment->getUserId() !== $userId && !$isAdmin) {
				throw new PermissionError('Only the author can delete this attachment');
			}

			$attachment->setDeletedAt(new DateTime());
			$this->mapper->update($attachment);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Attachment not found');
		}
	}

	/**
	 * Count attachments for a row
	 */
	public function countForRow(int $rowId): int {
		try {
			return $this->mapper->countForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error counting attachments: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Count attachments for multiple rows
	 * @param int[] $rowIds
	 * @return array<int, int>
	 */
	public function countForRows(array $rowIds): array {
		try {
			return $this->mapper->countForRows($rowIds);
		} catch (Exception $e) {
			$this->logger->error('Error counting attachments: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Delete all attachments for a row
	 */
	public function deleteAllForRow(int $rowId): void {
		try {
			$this->mapper->softDeleteAllForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting attachments for row: ' . $e->getMessage());
		}
	}

	/**
	 * Enrich an attachment with file info
	 */
	private function enrichAttachment(Attachment $attachment, string $userId): Attachment {
		$user = $this->userManager->get($attachment->getUserId());
		if ($user) {
			$attachment->setUserDisplayName($user->getDisplayName());
		}

		// Get file info
		if ($attachment->getFileId()) {
			try {
				$userFolder = $this->rootFolder->getUserFolder($userId);
				$nodes = $userFolder->getById($attachment->getFileId());
				if (!empty($nodes)) {
					$file = $nodes[0];
					$attachment->setFileName($file->getName());
					$attachment->setFilePath($userFolder->getRelativePath($file->getPath()));
					$attachment->setMimeType($file->getMimeType());
					$attachment->setFileSize($file->getSize());
				}
			} catch (NotFoundException $e) {
				$this->logger->warning('File not found for attachment: ' . $e->getMessage());
			} catch (\Exception $e) {
				$this->logger->error('Error getting file info: ' . $e->getMessage());
			}
		}

		return $attachment;
	}

	/**
	 * Enrich multiple attachments
	 * @param Attachment[] $attachments
	 * @return Attachment[]
	 */
	private function enrichAttachments(array $attachments, string $userId): array {
		return array_map(fn(Attachment $a) => $this->enrichAttachment($a, $userId), $attachments);
	}
}
