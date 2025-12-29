<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Controller;

use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Service\LabelService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class LabelController extends Controller {
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		private LabelService $service,
		private LoggerInterface $logger,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * Get all labels for a table
	 *
	 * @param int $tableId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function index(int $tableId): JSONResponse {
		try {
			$labels = $this->service->findAllForTable($tableId);
			return new JSONResponse($labels);
		} catch (\Exception $e) {
			$this->logger->error('Error getting labels: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error getting labels'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get a single label
	 *
	 * @param int $labelId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function show(int $labelId): JSONResponse {
		try {
			$label = $this->service->find($labelId);
			return new JSONResponse($label);
		} catch (NotFoundError $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error getting label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error getting label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create a new label
	 *
	 * @param int $tableId
	 * @param string $title
	 * @param string $color
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function create(int $tableId, string $title, string $color = '#0082c9'): JSONResponse {
		try {
			$label = $this->service->create($tableId, $title, $color, $this->userId);
			return new JSONResponse($label, Http::STATUS_CREATED);
		} catch (\Exception $e) {
			$this->logger->error('Error creating label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error creating label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update a label
	 *
	 * @param int $labelId
	 * @param string $title
	 * @param string $color
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function update(int $labelId, string $title, string $color): JSONResponse {
		try {
			$label = $this->service->update($labelId, $title, $color);
			return new JSONResponse($label);
		} catch (NotFoundError $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error updating label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error updating label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete a label
	 *
	 * @param int $labelId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function destroy(int $labelId): JSONResponse {
		try {
			$this->service->delete($labelId);
			return new JSONResponse(null, Http::STATUS_NO_CONTENT);
		} catch (NotFoundError $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error deleting label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get labels for a row
	 *
	 * @param int $rowId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function getRowLabels(int $rowId): JSONResponse {
		try {
			$labels = $this->service->getLabelsForRow($rowId);
			return new JSONResponse($labels);
		} catch (\Exception $e) {
			$this->logger->error('Error getting row labels: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error getting row labels'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Assign a label to a row
	 *
	 * @param int $rowId
	 * @param int $labelId
	 * @param int $tableId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function assignLabel(int $rowId, int $labelId, int $tableId): JSONResponse {
		try {
			$this->service->assignLabelToRow($rowId, $labelId, $tableId, $this->userId);
			return new JSONResponse(['success' => true], Http::STATUS_CREATED);
		} catch (NotFoundError $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			$this->logger->error('Error assigning label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error assigning label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Remove a label from a row
	 *
	 * @param int $rowId
	 * @param int $labelId
	 * @param int $tableId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function removeLabel(int $rowId, int $labelId, int $tableId): JSONResponse {
		try {
			$this->service->removeLabelFromRow($rowId, $labelId, $tableId, $this->userId);
			return new JSONResponse(null, Http::STATUS_NO_CONTENT);
		} catch (\Exception $e) {
			$this->logger->error('Error removing label: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error removing label'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Set all labels for a row (replaces existing)
	 *
	 * @param int $rowId
	 * @param int $tableId
	 * @param array $labelIds
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function setRowLabels(int $rowId, int $tableId, array $labelIds): JSONResponse {
		try {
			$this->service->setLabelsForRow($rowId, $labelIds, $tableId, $this->userId);
			$labels = $this->service->getLabelsForRow($rowId);
			return new JSONResponse($labels);
		} catch (\Exception $e) {
			$this->logger->error('Error setting row labels: ' . $e->getMessage());
			return new JSONResponse(['message' => 'Error setting row labels'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
