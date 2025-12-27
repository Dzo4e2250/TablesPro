<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TablesPro\Service\Support;

interface AuditLogServiceInterface {
	public function log(string $message, array $context): void;
}
