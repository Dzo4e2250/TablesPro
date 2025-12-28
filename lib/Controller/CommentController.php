<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Controller;

use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCA\TablesPro\Service\CommentService;
use OCA\TablesPro\Service\PermissionsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class CommentController extends OCSController {
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		private CommentService $service,
		private PermissionsService $permissionsService,
		private LoggerInterface $logger,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * Get all comments for a row
	 *
	 * @param int $rowId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function index(int $rowId): DataResponse {
		try {
			$comments = $this->service->findAllForRow($rowId, $this->userId);
			return new DataResponse($comments);
		} catch (\Exception $e) {
			$this->logger->error('Error getting comments: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error getting comments'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get a single comment
	 *
	 * @param int $commentId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function show(int $commentId): DataResponse {
		try {
			$comment = $this->service->find($commentId, $this->userId);
			return new DataResponse($comment);
		} catch (NotFoundError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error getting comment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error getting comment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create a new comment
	 *
	 * @param int $rowId
	 * @param int $tableId
	 * @param string $message
	 * @param int|null $replyTo
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function create(int $rowId, int $tableId, string $message, ?int $replyTo = null): DataResponse {
		try {
			$comment = $this->service->create($rowId, $tableId, $this->userId, $message, $replyTo);
			return new DataResponse($comment, Http::STATUS_CREATED);
		} catch (\Exception $e) {
			$this->logger->error('Error creating comment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error creating comment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update a comment
	 *
	 * @param int $commentId
	 * @param string $message
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function update(int $commentId, string $message): DataResponse {
		try {
			$comment = $this->service->update($commentId, $message, $this->userId);
			return new DataResponse($comment);
		} catch (NotFoundError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (PermissionError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (\Exception $e) {
			$this->logger->error('Error updating comment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error updating comment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete a comment
	 *
	 * @param int $commentId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function destroy(int $commentId): DataResponse {
		try {
			$this->service->delete($commentId, $this->userId);
			return new DataResponse(null, Http::STATUS_NO_CONTENT);
		} catch (NotFoundError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (PermissionError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting comment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error deleting comment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get comment count for a row
	 *
	 * @param int $rowId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function count(int $rowId): DataResponse {
		try {
			$count = $this->service->countForRow($rowId);
			return new DataResponse(['count' => $count]);
		} catch (\Exception $e) {
			$this->logger->error('Error counting comments: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error counting comments'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
