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

/** @template-extends QBMapper<Comment> */
class CommentMapper extends QBMapper {
	protected string $table = 'tables_comments';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, $this->table, Comment::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): Comment {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Get all comments for a row
	 * @return Comment[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'ASC');
		return $this->findEntities($qb);
	}

	/**
	 * Get all comments for a table
	 * @return Comment[]
	 * @throws Exception
	 */
	public function findAllForTable(int $tableId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'DESC');
		return $this->findEntities($qb);
	}

	/**
	 * Count comments for a row
	 * @throws Exception
	 */
	public function countForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('id', 'count'))
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();
		return (int)$count;
	}

	/**
	 * Count comments for multiple rows
	 * @param int[] $rowIds
	 * @return array<int, int> rowId => count
	 * @throws Exception
	 */
	public function countForRows(array $rowIds): array {
		if (empty($rowIds)) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('row_id', $qb->func()->count('id', 'count'))
			->from($this->table)
			->where($qb->expr()->in('row_id', $qb->createNamedParameter($rowIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->groupBy('row_id');
		$result = $qb->executeQuery();
		$counts = [];
		while ($row = $result->fetch()) {
			$counts[(int)$row['row_id']] = (int)$row['count'];
		}
		$result->closeCursor();
		return $counts;
	}

	/**
	 * Delete all comments for a row
	 * @throws Exception
	 */
	public function deleteAllForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Delete all comments for a table
	 * @throws Exception
	 */
	public function deleteAllForTable(int $tableId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}
}
