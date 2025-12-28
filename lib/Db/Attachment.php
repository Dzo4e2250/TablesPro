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
 * @method int|null getFileId()
 * @method void setFileId(?int $fileId)
 * @method string getType()
 * @method void setType(string $type)
 * @method string|null getData()
 * @method void setData(?string $data)
 * @method DateTime getCreatedAt()
 * @method void setCreatedAt(DateTime $createdAt)
 * @method DateTime|null getDeletedAt()
 * @method void setDeletedAt(?DateTime $deletedAt)
 */
class Attachment extends EntitySuper implements JsonSerializable {
	protected ?int $rowId = null;
	protected ?int $tableId = null;
	protected ?string $userId = null;
	protected ?int $fileId = null;
	protected string $type = 'file';
	protected ?string $data = null;
	protected ?DateTime $createdAt = null;
	protected ?DateTime $deletedAt = null;

	// Virtual properties for display
	protected ?string $userDisplayName = null;
	protected ?string $fileName = null;
	protected ?string $filePath = null;
	protected ?string $mimeType = null;
	protected ?int $fileSize = null;

	protected const VIRTUAL_PROPERTIES = ['userDisplayName', 'fileName', 'filePath', 'mimeType', 'fileSize'];

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('rowId', 'integer');
		$this->addType('tableId', 'integer');
		$this->addType('fileId', 'integer');
		$this->addType('createdAt', 'datetime');
		$this->addType('deletedAt', 'datetime');
	}

	public function setUserDisplayName(?string $displayName): void {
		$this->userDisplayName = $displayName;
	}

	public function setFileName(?string $fileName): void {
		$this->fileName = $fileName;
	}

	public function setFilePath(?string $filePath): void {
		$this->filePath = $filePath;
	}

	public function setMimeType(?string $mimeType): void {
		$this->mimeType = $mimeType;
	}

	public function setFileSize(?int $fileSize): void {
		$this->fileSize = $fileSize;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'rowId' => $this->rowId,
			'tableId' => $this->tableId,
			'userId' => $this->userId,
			'userDisplayName' => $this->userDisplayName,
			'fileId' => $this->fileId,
			'type' => $this->type,
			'data' => $this->data,
			'fileName' => $this->fileName,
			'filePath' => $this->filePath,
			'mimeType' => $this->mimeType,
			'fileSize' => $this->fileSize,
			'createdAt' => $this->createdAt?->format('c'),
			'deletedAt' => $this->deletedAt?->format('c'),
		];
	}
}
