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
 * @method int getTableId()
 * @method void setTableId(int $tableId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getColor()
 * @method void setColor(string $color)
 * @method string getCreatedBy()
 * @method void setCreatedBy(string $createdBy)
 * @method DateTime getCreatedAt()
 * @method void setCreatedAt(DateTime $createdAt)
 */
class Label extends EntitySuper implements JsonSerializable {
	protected ?int $tableId = null;
	protected ?string $title = null;
	protected ?string $color = null;
	protected ?string $createdBy = null;
	protected ?DateTime $createdAt = null;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('tableId', 'integer');
		$this->addType('createdAt', 'datetime');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'tableId' => $this->tableId,
			'title' => $this->title,
			'color' => $this->color,
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt?->format('c'),
		];
	}
}
