<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Controller;

use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCA\TablesPro\Service\AttachmentService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AttachmentController extends OCSController {
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		private AttachmentService $service,
		private LoggerInterface $logger,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * Get all attachments for a row
	 *
	 * @param int $rowId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function index(int $rowId): DataResponse {
		try {
			$attachments = $this->service->findAllForRow($rowId, $this->userId);
			return new DataResponse($attachments);
		} catch (\Exception $e) {
			$this->logger->error('Error getting attachments: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error getting attachments'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get a single attachment
	 *
	 * @param int $attachmentId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function show(int $attachmentId): DataResponse {
		try {
			$attachment = $this->service->find($attachmentId, $this->userId);
			return new DataResponse($attachment);
		} catch (NotFoundError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error getting attachment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error getting attachment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create a new attachment
	 *
	 * @param int $rowId
	 * @param int $tableId
	 * @param int $fileId
	 * @param string $type
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function create(int $rowId, int $tableId, int $fileId, string $type = 'file'): DataResponse {
		try {
			$attachment = $this->service->create($rowId, $tableId, $this->userId, $fileId, $type);
			return new DataResponse($attachment, Http::STATUS_CREATED);
		} catch (\Exception $e) {
			$this->logger->error('Error creating attachment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error creating attachment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete an attachment
	 *
	 * @param int $attachmentId
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function destroy(int $attachmentId): DataResponse {
		try {
			$this->service->delete($attachmentId, $this->userId);
			return new DataResponse(null, Http::STATUS_NO_CONTENT);
		} catch (NotFoundError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (PermissionError $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting attachment: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error deleting attachment'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get attachment count for a row
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
			$this->logger->error('Error counting attachments: ' . $e->getMessage());
			return new DataResponse(['message' => 'Error counting attachments'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
