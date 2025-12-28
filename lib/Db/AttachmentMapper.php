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

/** @template-extends QBMapper<Attachment> */
class AttachmentMapper extends QBMapper {
	protected string $table = 'tables_attachments';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, $this->table, Attachment::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): Attachment {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('deleted_at'));
		return $this->findEntity($qb);
	}

	/**
	 * Get all attachments for a row
	 * @return Attachment[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('deleted_at'))
			->orderBy('created_at', 'DESC');
		return $this->findEntities($qb);
	}

	/**
	 * Get all attachments for a table
	 * @return Attachment[]
	 * @throws Exception
	 */
	public function findAllForTable(int $tableId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('deleted_at'))
			->orderBy('created_at', 'DESC');
		return $this->findEntities($qb);
	}

	/**
	 * Count attachments for a row
	 * @throws Exception
	 */
	public function countForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('id', 'count'))
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('deleted_at'));
		$result = $qb->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();
		return (int)$count;
	}

	/**
	 * Count attachments for multiple rows
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
			->andWhere($qb->expr()->isNull('deleted_at'))
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
	 * Soft delete all attachments for a row
	 * @throws Exception
	 */
	public function softDeleteAllForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->table)
			->set('deleted_at', $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE))
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Hard delete all attachments for a table
	 * @throws Exception
	 */
	public function deleteAllForTable(int $tableId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}
}
