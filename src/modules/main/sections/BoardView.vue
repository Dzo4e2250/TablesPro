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
				<NcButton v-if="canEdit"
					type="primary"
					:aria-label="t('tablespro', 'Add card')"
					@click="onAddCardFull">
					<template #icon>
						<PlusIcon :size="20" />
					</template>
				</NcButton>
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
			<Container
				class="board-view__stacks"
				orientation="horizontal"
				drag-handle-selector=".board-stack__header"
				:drop-placeholder="stackDropPlaceholder"
				@drop="onStackDrop">
				<Draggable v-for="stack in orderedStacks" :key="stack.id">
					<BoardStack
						:stack="stack"
						:cards="getCardsForStack(stack.id)"
						:columns="columns"
						:title-column-id="view.cardTitleColumnId"
						:grouping-column-id="view.groupingColumnId"
						:can-edit="canEdit"
						@card-click="onCardClick"
						@card-edit="onCardEdit"
						@card-drop="onCardDrop"
						@quick-add-card="onQuickAddCard" />
				</Draggable>

				<!-- Stack for items without a value (not draggable) -->
				<BoardStack
					v-if="uncategorizedCards.length > 0"
					:stack="uncategorizedStack"
					:cards="uncategorizedCards"
					:columns="columns"
					:title-column-id="view.cardTitleColumnId"
					:grouping-column-id="view.groupingColumnId"
					:can-edit="canEdit"
					class="board-stack--uncategorized"
					@card-click="onCardClick"
					@card-edit="onCardEdit"
					@card-drop="onCardDrop"
					@quick-add-card="onQuickAddCard" />
			</Container>
		</div>
	</div>
</template>

<script>
import { Container, Draggable } from 'vue-smooth-dnd'
import { NcButton, NcEmptyContent } from '@nextcloud/vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
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
		Container,
		Draggable,
		NcButton,
		NcEmptyContent,
		CogIcon,
		PlusIcon,
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

	data() {
		return {
			stackOrder: [],
			stackDropPlaceholder: {
				className: 'stack-drop-placeholder',
				animationDuration: 200,
				showOnTop: true,
			},
		}
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

		orderedStacks() {
			if (!this.stackOrder || this.stackOrder.length === 0) {
				return this.stacks
			}

			// Sort stacks by stored order
			const stacksCopy = [...this.stacks]
			stacksCopy.sort((a, b) => {
				const indexA = this.stackOrder.indexOf(a.id)
				const indexB = this.stackOrder.indexOf(b.id)
				if (indexA === -1) return 1
				if (indexB === -1) return -1
				return indexA - indexB
			})
			return stacksCopy
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

	watch: {
		'view.id': {
			handler() {
				this.loadStackOrder()
			},
			immediate: true,
		},
	},

	mounted() {
		this.loadStackOrder()
	},

	methods: {
		t,
		...mapActions(useDataStore, ['updateRow', 'insertNewRow']),

		// Stack order methods
		getStackOrderStorageKey() {
			return `tablespro-stack-order-view-${this.view.id}`
		},

		loadStackOrder() {
			const storageKey = this.getStackOrderStorageKey()
			const stored = localStorage.getItem(storageKey)
			this.stackOrder = stored ? JSON.parse(stored) : []
		},

		saveStackOrder() {
			const storageKey = this.getStackOrderStorageKey()
			localStorage.setItem(storageKey, JSON.stringify(this.stackOrder))
		},

		onStackDrop({ removedIndex, addedIndex }) {
			if (removedIndex === null || addedIndex === null) return
			if (removedIndex === addedIndex) return

			// Get current order
			const currentOrder = this.orderedStacks.map(s => s.id)

			// Move the item
			const [removed] = currentOrder.splice(removedIndex, 1)
			currentOrder.splice(addedIndex, 0, removed)

			// Update and save
			this.stackOrder = currentOrder
			this.saveStackOrder()
		},

		getCardsForStack(stackId) {
			if (!this.groupingColumn) return []

			return this.rows.filter(row => {
				const cell = row.data?.find(d => d.columnId === this.view.groupingColumnId)
				return cell?.value === stackId
			})
		},

		onCardClick(row) {
			// Open card detail modal for board view
			emit('tables:card:detail', {
				row,
				columns: this.columns,
				isView: true,
				element: this.view,
				titleColumnId: this.view.cardTitleColumnId,
				groupingColumnId: this.view.groupingColumnId,
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

		onAddCardFull() {
			// Open full create row modal
			emit('tables:row:create', {
				columns: this.columns,
				isView: true,
				elementId: this.view.id,
				element: this.view,
			})
		},

		async onQuickAddCard({ stackValue, title }) {
			if (!this.canEdit) return

			// Build row data with title and grouping column
			// API expects format: { columnId: value } as object, not array
			const data = {}

			// Add grouping column value (must be a valid column ID > 0)
			const groupingId = parseInt(this.view.groupingColumnId, 10)
			if (groupingId && groupingId > 0 && stackValue !== null) {
				data[groupingId] = stackValue
			}

			// Add title to the title column (or first text column)
			const cardTitleId = parseInt(this.view.cardTitleColumnId, 10)
			const titleColumnId = (cardTitleId && cardTitleId > 0) ? cardTitleId : this.getFirstTextColumnId()
			if (titleColumnId && titleColumnId > 0 && title) {
				data[titleColumnId] = title
			}

			// Don't create if we have no valid data
			if (Object.keys(data).length === 0) {
				console.error('No valid columns to create card')
				return
			}

			try {
				await this.insertNewRow({
					viewId: this.view.id,
					tableId: this.view.tableId,
					data,
				})
			} catch (e) {
				console.error('Failed to create card:', e)
			}
		},

		getFirstTextColumnId() {
			// Find first text column that's in the view (has valid id > 0)
			const textTypes = ['text-line', 'text-long', 'text-rich']
			const textColumn = this.columns.find(c =>
				c.id > 0 && textTypes.includes(c.type),
			)
			return textColumn?.id || null
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
	padding-left: calc(var(--default-grid-baseline) * 4 + 44px); // Space for nav toggle button
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
	flex: 1 1 auto;
	display: flex;
	overflow-x: auto;
	overflow-y: hidden;
	min-height: 0;
	background-color: var(--color-main-background);
}

.board-view__stacks {
	display: flex;
	align-items: stretch;
	gap: $board-gap;
	padding: 0 $board-gap $board-gap;
	height: 100%;

	// vue-smooth-dnd styles
	:deep(.smooth-dnd-container) {
		display: flex;
		gap: $board-gap;
		height: 100%;
	}

	:deep(.smooth-dnd-draggable-wrapper) {
		height: 100%;
	}
}

// Stack drop placeholder
:deep(.stack-drop-placeholder) {
	background-color: var(--color-primary-element-light);
	border: 2px dashed var(--color-primary-element);
	border-radius: var(--border-radius-large);
	min-width: 280px;
	height: 100%;
	margin: 0 calc($board-gap / 2);
}

// Uncategorized stack should not be draggable
.board-stack--uncategorized {
	pointer-events: auto;
}
</style>
