<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Service;

use DateTime;
use OCA\TablesPro\Db\Activity;
use OCA\TablesPro\Db\ActivityMapper;
use OCP\DB\Exception;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ActivityService {

	public function __construct(
		private ActivityMapper $mapper,
		private IUserManager $userManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Get all activity for a row
	 * @return Activity[]
	 * @throws Exception
	 */
	public function findAllForRow(int $rowId, ?int $limit = 50, ?int $offset = null): array {
		$activities = $this->mapper->findAllForRow($rowId, $limit, $offset);
		return $this->enrichActivities($activities);
	}

	/**
	 * Get all activity for a table
	 * @return Activity[]
	 * @throws Exception
	 */
	public function findAllForTable(int $tableId, ?int $limit = 50, ?int $offset = null): array {
		$activities = $this->mapper->findAllForTable($tableId, $limit, $offset);
		return $this->enrichActivities($activities);
	}

	/**
	 * Log a row creation
	 */
	public function logRowCreate(int $tableId, int $rowId, string $userId, ?array $data = null): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_CREATE, Activity::SUBJECT_ROW, $rowId, $data);
	}

	/**
	 * Log a row update
	 */
	public function logRowUpdate(int $tableId, int $rowId, string $userId, ?array $changes = null): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_UPDATE, Activity::SUBJECT_ROW, $rowId, $changes);
	}

	/**
	 * Log a row deletion
	 */
	public function logRowDelete(int $tableId, int $rowId, string $userId): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_DELETE, Activity::SUBJECT_ROW, $rowId);
	}

	/**
	 * Log a row move (board stack change)
	 */
	public function logRowMove(int $tableId, int $rowId, string $userId, ?array $changes = null): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_MOVE, Activity::SUBJECT_ROW, $rowId, $changes);
	}

	/**
	 * Log a comment creation
	 */
	public function logCommentCreate(int $tableId, int $rowId, string $userId, int $commentId): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_COMMENT, Activity::SUBJECT_COMMENT, $commentId);
	}

	/**
	 * Log an attachment creation
	 */
	public function logAttachmentCreate(int $tableId, int $rowId, string $userId, int $attachmentId, ?array $data = null): void {
		$this->log($tableId, $rowId, $userId, Activity::ACTION_ATTACHMENT, Activity::SUBJECT_ATTACHMENT, $attachmentId, $data);
	}

	/**
	 * Log a table creation
	 */
	public function logTableCreate(int $tableId, string $userId, ?array $data = null): void {
		$this->log($tableId, null, $userId, Activity::ACTION_CREATE, Activity::SUBJECT_TABLE, $tableId, $data);
	}

	/**
	 * Log a table update
	 */
	public function logTableUpdate(int $tableId, string $userId, ?array $changes = null): void {
		$this->log($tableId, null, $userId, Activity::ACTION_UPDATE, Activity::SUBJECT_TABLE, $tableId, $changes);
	}

	/**
	 * Log a column creation
	 */
	public function logColumnCreate(int $tableId, string $userId, int $columnId, ?array $data = null): void {
		$this->log($tableId, null, $userId, Activity::ACTION_CREATE, Activity::SUBJECT_COLUMN, $columnId, $data);
	}

	/**
	 * Log a column update
	 */
	public function logColumnUpdate(int $tableId, string $userId, int $columnId, ?array $changes = null): void {
		$this->log($tableId, null, $userId, Activity::ACTION_UPDATE, Activity::SUBJECT_COLUMN, $columnId, $changes);
	}

	/**
	 * Log a column deletion
	 */
	public function logColumnDelete(int $tableId, string $userId, int $columnId): void {
		$this->log($tableId, null, $userId, Activity::ACTION_DELETE, Activity::SUBJECT_COLUMN, $columnId);
	}

	/**
	 * Generic log method
	 */
	private function log(
		int $tableId,
		?int $rowId,
		string $userId,
		string $action,
		string $subjectType,
		?int $subjectId = null,
		?array $changes = null
	): void {
		try {
			$activity = new Activity();
			$activity->setTableId($tableId);
			$activity->setRowId($rowId);
			$activity->setUserId($userId);
			$activity->setAction($action);
			$activity->setSubjectType($subjectType);
			$activity->setSubjectId($subjectId);
			$activity->setChangesArray($changes);
			$activity->setCreatedAt(new DateTime());

			$this->mapper->insert($activity);
		} catch (Exception $e) {
			$this->logger->error('Error logging activity: ' . $e->getMessage());
		}
	}

	/**
	 * Enrich an activity with user display name
	 */
	private function enrichActivity(Activity $activity): Activity {
		$user = $this->userManager->get($activity->getUserId());
		if ($user) {
			$activity->setUserDisplayName($user->getDisplayName());
		}
		return $activity;
	}

	/**
	 * Enrich multiple activities
	 * @param Activity[] $activities
	 * @return Activity[]
	 */
	private function enrichActivities(array $activities): array {
		return array_map(fn(Activity $a) => $this->enrichActivity($a), $activities);
	}

	/**
	 * Delete all activity for a row
	 */
	public function deleteAllForRow(int $rowId): void {
		try {
			$this->mapper->deleteAllForRow($rowId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting activity for row: ' . $e->getMessage());
		}
	}

	/**
	 * Delete all activity for a table
	 */
	public function deleteAllForTable(int $tableId): void {
		try {
			$this->mapper->deleteAllForTable($tableId);
		} catch (Exception $e) {
			$this->logger->error('Error deleting activity for table: ' . $e->getMessage());
		}
	}
}
