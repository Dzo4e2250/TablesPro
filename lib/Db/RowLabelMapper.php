<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<RowLabel> */
class RowLabelMapper extends QBMapper {
	protected string $table = 'tables_row_labels';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, $this->table, RowLabel::class);
	}

	/**
	 * Find a specific row-label assignment
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): RowLabel {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Find by row and label
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function findByRowAndLabel(int $rowId, int $labelId): RowLabel {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('label_id', $qb->createNamedParameter($labelId, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Get all label IDs for a row
	 * @return int[]
	 * @throws Exception
	 */
	public function findLabelIdsForRow(int $rowId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('label_id')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$ids = [];
		while ($row = $result->fetch()) {
			$ids[] = (int)$row['label_id'];
		}
		$result->closeCursor();
		return $ids;
	}

	/**
	 * Get all labels for multiple rows
	 * @param int[] $rowIds
	 * @return array<int, int[]> rowId => [labelIds]
	 * @throws Exception
	 */
	public function findLabelIdsForRows(array $rowIds): array {
		if (empty($rowIds)) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('row_id', 'label_id')
			->from($this->table)
			->where($qb->expr()->in('row_id', $qb->createNamedParameter($rowIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $qb->executeQuery();
		$mapping = [];
		while ($row = $result->fetch()) {
			$rowId = (int)$row['row_id'];
			if (!isset($mapping[$rowId])) {
				$mapping[$rowId] = [];
			}
			$mapping[$rowId][] = (int)$row['label_id'];
		}
		$result->closeCursor();
		return $mapping;
	}

	/**
	 * Get all row-label assignments for a row
	 * @return RowLabel[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * Delete all label assignments for a row
	 * @throws Exception
	 */
	public function deleteAllForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Delete all assignments for a label
	 * @throws Exception
	 */
	public function deleteAllForLabel(int $labelId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('label_id', $qb->createNamedParameter($labelId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Check if a row has a specific label
	 * @throws Exception
	 */
	public function rowHasLabel(int $rowId, int $labelId): bool {
		try {
			$this->findByRowAndLabel($rowId, $labelId);
			return true;
		} catch (DoesNotExistException) {
			return false;
		}
	}
}
