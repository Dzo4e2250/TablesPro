<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-view">
		<div class="board-view__header">
			<div class="board-view__title">
				<span v-if="view.emoji" class="board-view__emoji">{{ view.emoji }}</span>
				<h1>{{ view.title }}</h1>
			</div>
			<div class="board-view__actions">
				<NcButton v-if="canManageView"
					type="secondary"
					:aria-label="t('tablespro', 'View settings')"
					@click="$emit('edit-view')">
					<template #icon>
						<CogIcon :size="20" />
					</template>
					{{ t('tablespro', 'Settings') }}
				</NcButton>
			</div>
		</div>

		<div v-if="!groupingColumn" class="board-view__empty">
			<NcEmptyContent :name="t('tablespro', 'No grouping column configured')">
				<template #icon>
					<ViewColumnIcon />
				</template>
				<template #action>
					<NcButton v-if="canManageView" type="primary" @click="$emit('edit-view')">
						{{ t('tablespro', 'Configure board view') }}
					</NcButton>
				</template>
			</NcEmptyContent>
		</div>

		<div v-else class="board-view__board">
			<div class="board-view__stacks">
				<BoardStack
					v-for="stack in stacks"
					:key="stack.id"
					:stack="stack"
					:cards="getCardsForStack(stack.id)"
					:columns="columns"
					:title-column-id="view.cardTitleColumnId"
					:grouping-column-id="view.groupingColumnId"
					:can-edit="canEdit"
					@card-click="onCardClick"
					@card-edit="onCardEdit"
					@card-drop="onCardDrop"
					@add-card="onAddCard" />

				<!-- Stack for items without a value -->
				<BoardStack
					v-if="uncategorizedCards.length > 0"
					:stack="uncategorizedStack"
					:cards="uncategorizedCards"
					:columns="columns"
					:title-column-id="view.cardTitleColumnId"
					:grouping-column-id="view.groupingColumnId"
					:can-edit="canEdit"
					@card-click="onCardClick"
					@card-edit="onCardEdit"
					@card-drop="onCardDrop"
					@add-card="onAddCard" />
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcEmptyContent } from '@nextcloud/vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import ViewColumnIcon from 'vue-material-design-icons/ViewColumn.vue'
import BoardStack from '../partials/board/BoardStack.vue'
import { translate as t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import permissionsMixin from '../../../shared/components/ncTable/mixins/permissionsMixin.js'
import { mapActions } from 'pinia'
import { useDataStore } from '../../../store/data.js'

export default {
	name: 'BoardView',

	components: {
		NcButton,
		NcEmptyContent,
		CogIcon,
		ViewColumnIcon,
		BoardStack,
	},

	mixins: [permissionsMixin],

	props: {
		view: {
			type: Object,
			required: true,
		},
		columns: {
			type: Array,
			default: () => [],
		},
		rows: {
			type: Array,
			default: () => [],
		},
	},

	computed: {
		canManageView() {
			return this.canManageTable(this.view)
		},

		canEdit() {
			return this.canUpdateData(this.view)
		},

		groupingColumn() {
			if (!this.view.groupingColumnId) return null
			return this.columns.find(c => c.id === this.view.groupingColumnId)
		},

		stacks() {
			if (!this.groupingColumn) return []

			const options = this.groupingColumn.selectionOptions || []

			return options.map(option => ({
				id: option.id,
				title: option.label,
				value: option.id,
				color: option.color || null,
			}))
		},

		uncategorizedStack() {
			return {
				id: '__uncategorized__',
				title: t('tablespro', 'Uncategorized'),
				value: null,
				color: null,
			}
		},

		uncategorizedCards() {
			if (!this.groupingColumn) return []

			return this.rows.filter(row => {
				const cell = row.data?.find(d => d.columnId === this.view.groupingColumnId)
				return !cell?.value
			})
		},
	},

	methods: {
		t,
		...mapActions(useDataStore, ['updateRow']),

		getCardsForStack(stackId) {
			if (!this.groupingColumn) return []

			return this.rows.filter(row => {
				const cell = row.data?.find(d => d.columnId === this.view.groupingColumnId)
				return cell?.value === stackId
			})
		},

		onCardClick(row) {
			emit('tables:row:edit', {
				row,
				columns: this.columns,
				isView: true,
				elementId: this.view.id,
				element: this.view,
			})
		},

		onCardEdit(row) {
			this.onCardClick(row)
		},

		async onCardDrop({ card, stackId, stackValue }) {
			if (!this.canEdit) return

			// Update the row's grouping column value
			const data = [{
				columnId: this.view.groupingColumnId,
				value: stackValue,
			}]

			try {
				await this.updateRow({
					id: card.id,
					isView: true,
					elementId: this.view.id,
					data,
				})
			} catch (e) {
				console.error('Failed to move card:', e)
			}
		},

		onAddCard(stackId) {
			// Emit event to open create row modal with pre-filled grouping value
			const stack = this.stacks.find(s => s.id === stackId) || this.uncategorizedStack
			emit('tables:row:create', {
				columns: this.columns,
				isView: true,
				elementId: this.view.id,
				element: this.view,
				prefill: {
					[this.view.groupingColumnId]: stack.value,
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
$board-gap: calc(var(--default-grid-baseline) * 4);

.board-view {
	display: flex;
	flex-direction: column;
	height: 100%;
	min-height: 0;
}

.board-view__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 4);
	border-bottom: 1px solid var(--color-border);
	flex-shrink: 0;
}

.board-view__title {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);

	h1 {
		margin: 0;
		font-size: 20px;
		font-weight: 600;
	}
}

.board-view__emoji {
	font-size: 24px;
}

.board-view__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
}

.board-view__empty {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
}

.board-view__board {
	flex: 1;
	overflow-x: auto;
	overflow-y: hidden;
	min-height: 0;
}

.board-view__stacks {
	display: flex;
	gap: $board-gap;
	padding: $board-gap;
	height: 100%;
	min-height: 400px;
}
</style>
