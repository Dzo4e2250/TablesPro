<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Controller;

use OCA\TablesPro\Service\ActivityService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ActivityController extends Controller {
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		private ActivityService $service,
		private LoggerInterface $logger,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * Get all activity for a row
	 *
	 * @param int $rowId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function indexForRow(int $rowId, ?int $limit = 50, ?int $offset = null): JSONResponse {
		try {
			$activities = $this->service->findAllForRow($rowId, $limit, $offset);
			return new JSONResponse($activities);
		} catch (\Exception $e) {
			$this->logger->error('Error getting activity: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error getting activity'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get all activity for a table
	 *
	 * @param int $tableId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function indexForTable(int $tableId, ?int $limit = 50, ?int $offset = null): JSONResponse {
		try {
			$activities = $this->service->findAllForTable($tableId, $limit, $offset);
			return new JSONResponse($activities);
		} catch (\Exception $e) {
			$this->logger->error('Error getting activity: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error getting activity'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
