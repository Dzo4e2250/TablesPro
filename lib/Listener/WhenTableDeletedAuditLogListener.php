<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TablesPro\Listener;

use OCA\TablesPro\Event\TableDeletedEvent;
use OCA\TablesPro\Service\Support\AuditLogServiceInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|TableDeletedEvent>
 */
final class WhenTableDeletedAuditLogListener implements IEventListener {
	public function __construct(
		protected AuditLogServiceInterface $auditLogService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof TableDeletedEvent)) {
			return;
		}

		$table = $event->getTable();

		$this->auditLogService->log("Table with ID: $table->id was deleted", [
			'table' => $table->jsonSerialize(),
		]);
	}
}
