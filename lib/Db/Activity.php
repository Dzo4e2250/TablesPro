<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Db;

use DateTime;
use JsonSerializable;

/**
 * Activity log entity for tracking changes
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getTableId()
 * @method void setTableId(int $tableId)
 * @method int|null getRowId()
 * @method void setRowId(?int $rowId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method string getSubjectType()
 * @method void setSubjectType(string $subjectType)
 * @method int|null getSubjectId()
 * @method void setSubjectId(?int $subjectId)
 * @method string|null getChanges()
 * @method void setChanges(?string $changes)
 * @method DateTime getCreatedAt()
 * @method void setCreatedAt(DateTime $createdAt)
 */
class Activity extends EntitySuper implements JsonSerializable {
	// Action constants
	public const ACTION_CREATE = 'create';
	public const ACTION_UPDATE = 'update';
	public const ACTION_DELETE = 'delete';
	public const ACTION_COMMENT = 'comment';
	public const ACTION_ATTACHMENT = 'attachment';
	public const ACTION_MOVE = 'move';

	// Subject type constants
	public const SUBJECT_ROW = 'row';
	public const SUBJECT_COLUMN = 'column';
	public const SUBJECT_TABLE = 'table';
	public const SUBJECT_VIEW = 'view';
	public const SUBJECT_COMMENT = 'comment';
	public const SUBJECT_ATTACHMENT = 'attachment';

	protected ?int $tableId = null;
	protected ?int $rowId = null;
	protected ?string $userId = null;
	protected ?string $action = null;
	protected ?string $subjectType = null;
	protected ?int $subjectId = null;
	protected ?string $changes = null;
	protected ?DateTime $createdAt = null;

	// Virtual properties
	protected ?string $userDisplayName = null;

	protected const VIRTUAL_PROPERTIES = ['userDisplayName'];

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('tableId', 'integer');
		$this->addType('rowId', 'integer');
		$this->addType('subjectId', 'integer');
		$this->addType('createdAt', 'datetime');
	}

	public function setUserDisplayName(?string $displayName): void {
		$this->userDisplayName = $displayName;
	}

	public function getUserDisplayName(): ?string {
		return $this->userDisplayName;
	}

	/**
	 * Get changes as array
	 */
	public function getChangesArray(): ?array {
		if ($this->changes === null || $this->changes === '') {
			return null;
		}
		return json_decode($this->changes, true);
	}

	/**
	 * Set changes from array
	 */
	public function setChangesArray(?array $changes): void {
		if ($changes === null) {
			$this->changes = null;
		} else {
			$this->changes = json_encode($changes);
		}
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'tableId' => $this->tableId,
			'rowId' => $this->rowId,
			'userId' => $this->userId,
			'userDisplayName' => $this->userDisplayName,
			'action' => $this->action,
			'subjectType' => $this->subjectType,
			'subjectId' => $this->subjectId,
			'changes' => $this->getChangesArray(),
			'createdAt' => $this->createdAt?->format('c'),
		];
	}
}
