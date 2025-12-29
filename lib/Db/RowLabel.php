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
 * @method int getLabelId()
 * @method void setLabelId(int $labelId)
 * @method DateTime getCreatedAt()
 * @method void setCreatedAt(DateTime $createdAt)
 */
class RowLabel extends EntitySuper implements JsonSerializable {
	protected ?int $rowId = null;
	protected ?int $labelId = null;
	protected ?DateTime $createdAt = null;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('rowId', 'integer');
		$this->addType('labelId', 'integer');
		$this->addType('createdAt', 'datetime');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'rowId' => $this->rowId,
			'labelId' => $this->labelId,
			'createdAt' => $this->createdAt?->format('c'),
		];
	}
}
