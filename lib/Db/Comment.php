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
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getRowId()
 * @method void setRowId(int $rowId)
 * @method int getTableId()
 * @method void setTableId(int $tableId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getMessage()
 * @method void setMessage(string $message)
 * @method DateTime getCreatedAt()
 * @method void setCreatedAt(DateTime $createdAt)
 * @method DateTime|null getUpdatedAt()
 * @method void setUpdatedAt(?DateTime $updatedAt)
 * @method int|null getReplyTo()
 * @method void setReplyTo(?int $replyTo)
 */
class Comment extends EntitySuper implements JsonSerializable {
	protected ?int $rowId = null;
	protected ?int $tableId = null;
	protected ?string $userId = null;
	protected ?string $message = null;
	protected ?DateTime $createdAt = null;
	protected ?DateTime $updatedAt = null;
	protected ?int $replyTo = null;

	// Virtual properties for display
	protected ?string $userDisplayName = null;
	protected ?array $mentions = null;

	protected const VIRTUAL_PROPERTIES = ['userDisplayName', 'mentions'];

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('rowId', 'integer');
		$this->addType('tableId', 'integer');
		$this->addType('replyTo', 'integer');
		$this->addType('createdAt', 'datetime');
		$this->addType('updatedAt', 'datetime');
	}

	public function setUserDisplayName(?string $displayName): void {
		$this->userDisplayName = $displayName;
	}

	public function getUserDisplayName(): ?string {
		return $this->userDisplayName;
	}

	public function setMentions(?array $mentions): void {
		$this->mentions = $mentions;
	}

	public function getMentions(): ?array {
		return $this->mentions;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'rowId' => $this->rowId,
			'tableId' => $this->tableId,
			'userId' => $this->userId,
			'userDisplayName' => $this->userDisplayName,
			'message' => $this->message,
			'createdAt' => $this->createdAt?->format('c'),
			'updatedAt' => $this->updatedAt?->format('c'),
			'replyTo' => $this->replyTo,
			'mentions' => $this->mentions,
		];
	}
}
