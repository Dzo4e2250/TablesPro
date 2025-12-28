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

/** @template-extends QBMapper<Activity> */
class ActivityMapper extends QBMapper {
	protected string $table = 'tables_activity';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, $this->table, Activity::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): Activity {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Get all activity for a row
	 * @return Activity[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId, ?int $limit = null, ?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'DESC');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * Get all activity for a table
	 * @return Activity[]
	 * @throws Exception
	 */
	public function findAllForTable(int $tableId, ?int $limit = null, ?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'DESC');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * Get recent activity for a user (across all tables)
	 * @return Activity[]
	 * @throws Exception
	 */
	public function findRecentForUser(string $userId, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->table)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);
		return $this->findEntities($qb);
	}

	/**
	 * Delete all activity for a row
	 * @throws Exception
	 */
	public function deleteAllForRow(int $rowId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('row_id', $qb->createNamedParameter($rowId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Delete all activity for a table
	 * @throws Exception
	 */
	public function deleteAllForTable(int $tableId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->eq('table_id', $qb->createNamedParameter($tableId, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Delete old activity entries
	 * @throws Exception
	 */
	public function deleteOlderThan(\DateTime $date): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->table)
			->where($qb->expr()->lt('created_at', $qb->createNamedParameter($date, IQueryBuilder::PARAM_DATE)));
		return $qb->executeStatement();
	}
}
