<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-stack" :class="{ 'board-stack--drag-over': isDragOver }" :data-stack-id="stack.id">
		<div class="board-stack__header">
			<div class="board-stack__title-wrapper">
				<span v-if="stack.color"
					class="board-stack__color"
					:style="{ backgroundColor: stack.color }" />
				<h3 class="board-stack__title">{{ stack.title }}</h3>
			</div>
			<NcButton v-if="canEdit"
				type="tertiary"
				class="board-stack__add-btn"
				:aria-label="t('tablespro', 'Add card')"
				@click="startAddCard">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
			</NcButton>
		</div>

		<div class="board-stack__cards">
			<Container
				group-name="stack"
				orientation="vertical"
				:get-child-payload="getCardPayload"
				drag-class="board-card--ghost"
				drop-class="board-card--ghost-drop"
				:drop-placeholder="dropPlaceholderOptions"
				:animation-duration="150"
				@should-accept-drop="() => canEdit"
				@drag-enter="onDragEnter"
				@drag-leave="onDragLeave"
				@drop="onCardDrop">
				<Draggable v-for="card in cards" :key="card.id">
					<BoardCard
						:row="card"
						:columns="columns"
						:title-column-id="titleColumnId"
						:grouping-column-id="groupingColumnId"
						:can-edit="canEdit"
						@click="$emit('card-click', $event)"
						@edit="$emit('card-edit', $event)" />
				</Draggable>
				<!-- Empty state inside container for better drop detection -->
				<div v-if="cards.length === 0 && !isAddingCard" class="board-stack__empty-drop">
					{{ t('tablespro', 'Drop cards here') }}
				</div>
			</Container>

			<!-- Inline add card form -->
			<div v-if="isAddingCard" class="board-stack__add-form">
				<input
					ref="addCardInput"
					v-model="newCardTitle"
					type="text"
					class="board-stack__add-input"
					:placeholder="t('tablespro', 'Card title...')"
					@keydown.enter="submitNewCard"
					@keydown.escape="cancelAddCard"
					@blur="onInputBlur">
			</div>
		</div>
	</div>
</template>

<script>
import { Container, Draggable } from 'vue-smooth-dnd'
import { NcButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import BoardCard from './BoardCard.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BoardStack',

	components: {
		Container,
		Draggable,
		NcButton,
		PlusIcon,
		BoardCard,
	},

	props: {
		stack: {
			type: Object,
			required: true,
		},
		cards: {
			type: Array,
			default: () => [],
		},
		columns: {
			type: Array,
			default: () => [],
		},
		titleColumnId: {
			type: Number,
			default: null,
		},
		groupingColumnId: {
			type: Number,
			required: true,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			isDragOver: false,
			dropPlaceholderOptions: {
				className: 'board-card-drop-placeholder',
				animationDuration: 200,
				showOnTop: true,
			},
			isAddingCard: false,
			newCardTitle: '',
		}
	},

	methods: {
		t,

		getCardPayload(index) {
			return this.cards[index]
		},

		onDragEnter() {
			this.isDragOver = true
		},

		onDragLeave() {
			this.isDragOver = false
		},

		onCardDrop(dropResult) {
			this.isDragOver = false
			const { addedIndex, removedIndex, payload } = dropResult

			// Card was dropped into this stack
			if (addedIndex !== null) {
				this.$emit('card-drop', {
					card: payload,
					stackId: this.stack.id,
					stackValue: this.stack.value,
					addedIndex,
					removedIndex,
				})
			}
		},

		startAddCard() {
			this.isAddingCard = true
			this.newCardTitle = ''
			this.$nextTick(() => {
				this.$refs.addCardInput?.focus()
			})
		},

		submitNewCard() {
			const title = this.newCardTitle.trim()
			if (title) {
				this.$emit('quick-add-card', {
					stackId: this.stack.id,
					stackValue: this.stack.value,
					title,
				})
			}
			this.isAddingCard = false
			this.newCardTitle = ''
		},

		cancelAddCard() {
			this.isAddingCard = false
			this.newCardTitle = ''
		},

		onInputBlur() {
			// Small delay to allow Enter key to fire first
			setTimeout(() => {
				if (this.isAddingCard) {
					this.submitNewCard()
				}
			}, 100)
		},
	},
}
</script>

<style lang="scss" scoped>
$stack-min-width: 216px;
$stack-max-width: 300px;
$stack-gap: calc(var(--default-grid-baseline) * 3);

.board-stack {
	display: flex;
	flex-direction: column;
	flex: 0 0 $stack-max-width;
	min-width: $stack-min-width;
	max-width: $stack-max-width;
	height: 100%;
	background-color: var(--color-main-background);
	transition: box-shadow 0.2s ease;

	&--drag-over {
		box-shadow: 0 0 12px rgba(var(--color-primary-element-rgb), 0.3);
		background-color: var(--color-primary-element-light);
	}
}

.board-stack__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: calc(var(--default-grid-baseline) * 2);
	background-color: var(--color-main-background);
	position: sticky;
	top: 0;
	z-index: 100;
	cursor: grab;

	// Smooth fade out gradient like Deck
	&::after {
		content: '';
		display: block;
		position: absolute;
		width: 100%;
		height: $stack-gap;
		bottom: 0;
		left: 0;
		z-index: 99;
		background: linear-gradient(180deg, var(--color-main-background) 0%, transparent 100%);
		transform: translateY(100%);
		pointer-events: none;
	}
}

.board-stack__title-wrapper {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	min-width: 0;
	flex: 1;
}

.board-stack__color {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	flex-shrink: 0;
}

.board-stack__title {
	margin: 0;
	font-size: 21px;
	font-weight: 600;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.board-stack__add-btn {
	flex-shrink: 0;
	color: var(--color-text-maxcontrast) !important;

	&:hover {
		color: var(--color-main-text) !important;
	}
}

.board-stack__cards {
	flex: 1 1 auto;
	overflow-y: auto;
	overflow-x: hidden;
	padding: 0 $stack-gap;
	scrollbar-gutter: stable;
	min-height: 0;

	// vue-smooth-dnd container styles
	:deep(.smooth-dnd-container) {
		display: flex;
		flex-direction: column;
		gap: $stack-gap;
		min-height: 100%;
		padding: $stack-gap 0;
	}

	:deep(.smooth-dnd-container.vertical) {
		min-height: 100%;
	}

	:deep(.smooth-dnd-draggable-wrapper) {
		overflow: visible;
	}
}

.board-stack__empty-drop {
	display: flex;
	align-items: center;
	justify-content: center;
	flex: 1;
	min-height: 100px;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.board-stack__add-form {
	padding: $stack-gap;
	padding-top: 0;
}

.board-stack__add-input {
	width: 100%;
	padding: calc(var(--default-grid-baseline) * 2);
	border: 2px solid var(--color-primary-element);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
	font-size: 14px;
	outline: none;

	&::placeholder {
		color: var(--color-text-maxcontrast);
	}
}

// Drag ghost styles
:deep(.board-card--ghost) {
	opacity: 0.8;
	transform: rotate(3deg);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

:deep(.board-card--ghost-drop) {
	border: 2px dashed var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}

// Drop placeholder - shows where card will land
:deep(.board-card-drop-placeholder) {
	background-color: var(--color-primary-element-light);
	border: 2px dashed var(--color-primary-element);
	border-radius: var(--border-radius-large);
	min-height: 80px;
	margin: $stack-gap 0;
}
</style>
