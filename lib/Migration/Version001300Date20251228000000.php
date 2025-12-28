<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add comments and attachments tables for Deck-like features
 */
class Version001300Date20251228000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// Create comments table
		if (!$schema->hasTable('tables_comments')) {
			$table = $schema->createTable('tables_comments');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('row_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('table_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('message', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->addColumn('updated_at', Types::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('reply_to', Types::BIGINT, [
				'notnull' => false,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['row_id'], 'tables_comment_row_idx');
			$table->addIndex(['table_id'], 'tables_comment_table_idx');
			$table->addIndex(['user_id'], 'tables_comment_user_idx');
		}

		// Create attachments table
		if (!$schema->hasTable('tables_attachments')) {
			$table = $schema->createTable('tables_attachments');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('row_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('table_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => false,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => 'file',
			]);
			$table->addColumn('data', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->addColumn('deleted_at', Types::DATETIME, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['row_id'], 'tables_attach_row_idx');
			$table->addIndex(['table_id'], 'tables_attach_table_idx');
		}

		// Create activity log table
		if (!$schema->hasTable('tables_activity')) {
			$table = $schema->createTable('tables_activity');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('table_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('row_id', Types::BIGINT, [
				'notnull' => false,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('action', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('subject_type', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('subject_id', Types::BIGINT, [
				'notnull' => false,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('changes', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['table_id'], 'tables_activity_table_idx');
			$table->addIndex(['row_id'], 'tables_activity_row_idx');
			$table->addIndex(['user_id'], 'tables_activity_user_idx');
			$table->addIndex(['created_at'], 'tables_activity_created_idx');
		}

		return $schema;
	}
}
