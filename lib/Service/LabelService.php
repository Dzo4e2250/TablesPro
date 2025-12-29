<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Service;

use DateTime;
use OCA\TablesPro\Db\Label;
use OCA\TablesPro\Db\LabelMapper;
use OCA\TablesPro\Db\RowLabel;
use OCA\TablesPro\Db\RowLabelMapper;
use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class LabelService {

	public function __construct(
		private LabelMapper $labelMapper,
		private RowLabelMapper $rowLabelMapper,
		private ActivityService $activityService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Get all labels for a table
	 * @return Label[]
	 * @throws Exception
	 */
	public function findAllForTable(int $tableId): array {
		return $this->labelMapper->findAllForTable($tableId);
	}

	/**
	 * Get a single label
	 * @throws NotFoundError
	 */
	public function find(int $id): Label {
		try {
			return $this->labelMapper->find($id);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$this->logger->error('Label not found: ' . $e->getMessage());
			throw new NotFoundError('Label not found');
		}
	}

	/**
	 * Create a new label
	 * @throws Exception
	 */
	public function create(int $tableId, string $title, string $color, string $userId): Label {
		$label = new Label();
		$label->setTableId($tableId);
		$label->setTitle($title);
		$label->setColor($color);
		$label->setCreatedBy($userId);
		$label->setCreatedAt(new DateTime());

		$label = $this->labelMapper->insert($label);

		// Log activity
		$this->activityService->logLabelCreate($tableId, null, $userId, $label->getId());

		return $label;
	}

	/**
	 * Update a label
	 * @throws NotFoundError
	 * @throws Exception
	 */
	public function update(int $id, string $title, string $color): Label {
		try {
			$label = $this->labelMapper->find($id);
			$label->setTitle($title);
			$label->setColor($color);
			return $this->labelMapper->update($label);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Label not found');
		}
	}

	/**
	 * Delete a label
	 * @throws NotFoundError
	 * @throws Exception
	 */
	public function delete(int $id): void {
		try {
			$label = $this->labelMapper->find($id);
			// First delete all row assignments
			$this->rowLabelMapper->deleteAllForLabel($id);
			// Then delete the label
			$this->labelMapper->delete($label);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Label not found');
		}
	}

	/**
	 * Get all labels for a row (with full label data)
	 * @return Label[]
	 * @throws Exception
	 */
	public function getLabelsForRow(int $rowId): array {
		$labelIds = $this->rowLabelMapper->findLabelIdsForRow($rowId);
		if (empty($labelIds)) {
			return [];
		}

		$labels = [];
		foreach ($labelIds as $labelId) {
			try {
				$labels[] = $this->labelMapper->find($labelId);
			} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
				// Label was deleted, skip
			}
		}
		return $labels;
	}

	/**
	 * Get labels for multiple rows
	 * @param int[] $rowIds
	 * @param int $tableId
	 * @return array<int, Label[]> rowId => [Label objects]
	 * @throws Exception
	 */
	public function getLabelsForRows(array $rowIds, int $tableId): array {
		if (empty($rowIds)) {
			return [];
		}

		// Get all labels for the table (for efficient lookup)
		$allLabels = $this->labelMapper->findAllForTable($tableId);
		$labelMap = [];
		foreach ($allLabels as $label) {
			$labelMap[$label->getId()] = $label;
		}

		// Get row-label assignments
		$rowLabelMapping = $this->rowLabelMapper->findLabelIdsForRows($rowIds);

		// Build the result
		$result = [];
		foreach ($rowLabelMapping as $rowId => $labelIds) {
			$result[$rowId] = [];
			foreach ($labelIds as $labelId) {
				if (isset($labelMap[$labelId])) {
					$result[$rowId][] = $labelMap[$labelId];
				}
			}
		}

		return $result;
	}

	/**
	 * Assign a label to a row
	 * @throws NotFoundError
	 * @throws Exception
	 */
	public function assignLabelToRow(int $rowId, int $labelId, int $tableId, string $userId): void {
		// Check if label exists
		try {
			$this->labelMapper->find($labelId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			throw new NotFoundError('Label not found');
		}

		// Check if already assigned
		if ($this->rowLabelMapper->rowHasLabel($rowId, $labelId)) {
			return; // Already assigned, nothing to do
		}

		$rowLabel = new RowLabel();
		$rowLabel->setRowId($rowId);
		$rowLabel->setLabelId($labelId);
		$rowLabel->setCreatedAt(new DateTime());

		$this->rowLabelMapper->insert($rowLabel);

		// Log activity
		$this->activityService->logLabelAssign($tableId, $rowId, $userId, $labelId);
	}

	/**
	 * Remove a label from a row
	 * @throws Exception
	 */
	public function removeLabelFromRow(int $rowId, int $labelId, int $tableId, string $userId): void {
		try {
			$rowLabel = $this->rowLabelMapper->findByRowAndLabel($rowId, $labelId);
			$this->rowLabelMapper->delete($rowLabel);

			// Log activity
			$this->activityService->logLabelRemove($tableId, $rowId, $userId, $labelId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			// Not assigned, nothing to do
		}
	}

	/**
	 * Set labels for a row (replaces all existing labels)
	 * @param int[] $labelIds
	 * @throws Exception
	 */
	public function setLabelsForRow(int $rowId, array $labelIds, int $tableId, string $userId): void {
		// Get current labels
		$currentLabelIds = $this->rowLabelMapper->findLabelIdsForRow($rowId);

		// Find labels to add
		$toAdd = array_diff($labelIds, $currentLabelIds);
		// Find labels to remove
		$toRemove = array_diff($currentLabelIds, $labelIds);

		// Add new labels
		foreach ($toAdd as $labelId) {
			try {
				$this->assignLabelToRow($rowId, $labelId, $tableId, $userId);
			} catch (NotFoundError $e) {
				// Label doesn't exist, skip
			}
		}

		// Remove old labels
		foreach ($toRemove as $labelId) {
			$this->removeLabelFromRow($rowId, $labelId, $tableId, $userId);
		}
	}

	/**
	 * Delete all labels for a table
	 */
	public function deleteAllForTable(int $tableId): void {
		try {
			// First get all label IDs for the table
			$labels = $this->labelMapper->findAllForTable($tableId);
			foreach ($labels as $label) {
				$this->rowLabelMapper->deleteAllForLabel($label->getId());
			}
			// Then delete all labels
			$this->labelMapper->deleteAllForTable($tableId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting labels for table: ' . $e->getMessage());
		}
	}

	/**
	 * Delete all label assignments for a row
	 */
	public function deleteAllForRow(int $rowId): void {
		try {
			$this->rowLabelMapper->deleteAllForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting labels for row: ' . $e->getMessage());
		}
	}
}
