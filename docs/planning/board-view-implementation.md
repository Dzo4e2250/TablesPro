# Board View Implementation Plan

## Overview

Add a Kanban/Board view type to TablesPro, allowing users to view table data as draggable cards organized in columns based on a selection field.

## Architecture

### Data Model Mapping
| Deck Concept | TablesPro Mapping |
|--------------|-------------------|
| Board | Table/View |
| Stack (column) | Selection field value |
| Card | Row |

### View Type
Views will have a new `viewType` property:
- `table` (default) - Traditional table view
- `board` - Kanban board view

Board views require an additional `groupingColumnId` property to define which selection column determines the stacks.

---

## Phase 1: Backend Changes

### 1.1 Database Migration
**File:** `lib/Migration/Version001100Date20251228000000.php`

Add columns to `tables_views`:
```sql
ALTER TABLE tables_views ADD COLUMN view_type VARCHAR(20) DEFAULT 'table';
ALTER TABLE tables_views ADD COLUMN grouping_column_id INT DEFAULT NULL;
```

### 1.2 Update View Entity
**File:** `lib/Db/View.php`

Add properties:
```php
protected ?string $viewType = 'table';
protected ?int $groupingColumnId = null;
```

Update `jsonSerialize()` to include new fields.

### 1.3 Update View Service
**File:** `lib/Service/ViewService.php`

- Accept `viewType` and `groupingColumnId` when creating/updating views
- Validate that `groupingColumnId` references a selection-type column

---

## Phase 2: Frontend - Core Components

### 2.1 Install Dependencies
```bash
npm install vue-smooth-dnd
```

### 2.2 Create Board Components

#### BoardView.vue
**File:** `src/modules/main/sections/BoardView.vue`

Main container component:
- Horizontal scrolling container for stacks
- Drag & drop container for reordering stacks (optional)
- Controls bar (similar to table view)

```vue
<template>
  <div class="board-wrapper">
    <Controls :element="view" />
    <div class="board">
      <Container orientation="horizontal" @drop="onDropStack">
        <Draggable v-for="stack in stacks" :key="stack.id">
          <BoardStack
            :stack="stack"
            :cards="getCardsForStack(stack.id)"
            @drop-card="onDropCard" />
        </Draggable>
      </Container>
    </div>
  </div>
</template>
```

#### BoardStack.vue
**File:** `src/modules/main/partials/board/BoardStack.vue`

Stack/column component:
- Header with stack title (selection value)
- Card count badge
- Vertical container for cards
- "Add card" button

```vue
<template>
  <div class="stack">
    <div class="stack__header">
      <h3>{{ stack.title }}</h3>
      <span class="count">{{ cards.length }}</span>
    </div>
    <Container group-name="cards" @drop="onDrop">
      <Draggable v-for="card in cards" :key="card.id">
        <BoardCard :card="card" @click="openCard" />
      </Draggable>
    </Container>
  </div>
</template>
```

#### BoardCard.vue
**File:** `src/modules/main/partials/board/BoardCard.vue`

Card component showing row data:
- Title (first text column or configurable)
- Labels (other selection columns)
- Badges (dates, numbers, assigned users)
- Click to open row edit modal

```vue
<template>
  <div class="card" @click="$emit('click', card)">
    <h4>{{ cardTitle }}</h4>
    <div class="card-labels">
      <span v-for="label in labels" :key="label.id"
            :style="{ backgroundColor: label.color }">
        {{ label.value }}
      </span>
    </div>
    <div class="card-badges">
      <CardBadges :card="card" />
    </div>
  </div>
</template>
```

---

## Phase 3: Frontend - Integration

### 3.1 Update ViewSettings.vue
**File:** `src/modules/modals/ViewSettings.vue`

Add view type selection:
```vue
<NcAppSettingsSection id="view-type" :name="t('tablespro', 'View Type')">
  <NcSelect v-model="viewType" :options="viewTypeOptions" />

  <div v-if="viewType === 'board'">
    <label>{{ t('tablespro', 'Group by column') }}</label>
    <NcSelect v-model="groupingColumnId"
              :options="selectionColumns" />
  </div>
</NcAppSettingsSection>
```

### 3.2 Update MainWrapper.vue
**File:** `src/modules/main/sections/MainWrapper.vue`

Conditionally render based on view type:
```vue
<template>
  <div>
    <BoardView v-if="isView && element.viewType === 'board'"
      :view="element"
      :columns="columns"
      :rows="rows" />
    <CustomView v-else-if="isView"
      :view="element"
      :columns="columns"
      :rows="rows" />
    <CustomTable v-else ... />
  </div>
</template>
```

### 3.3 Update Data Store
**File:** `src/store/data.js`

Add action for moving cards between stacks:
```javascript
async moveCardToStack({ rowId, columnId, newValue, isView, elementId }) {
  // Update the row's column value
  const data = [{ columnId, value: newValue }]
  return await this.updateRow({ id: rowId, isView, elementId, data })
}
```

---

## Phase 4: Styling

### 4.1 Board CSS Variables
**File:** `src/modules/main/sections/board/variables.scss`

```scss
$card-min-width: 280px;
$card-max-width: 320px;
$card-padding: calc(var(--default-grid-baseline) * 2);
$card-gap: calc(var(--default-grid-baseline) * 2);
$stack-gap: calc(var(--default-grid-baseline) * 3);
$board-gap: calc(var(--default-grid-baseline) * 4);
```

### 4.2 Component Styles
- Cards: rounded corners, subtle shadow, hover effect
- Stacks: vertical scroll, sticky header
- Board: horizontal scroll, gap between stacks

---

## Phase 5: Additional Features

### 5.1 Card Configuration (optional)
Allow users to configure:
- Which column is the card title
- Which columns show as labels
- Which columns show as badges

### 5.2 Stack Actions (optional)
- Add new card to stack
- Archive all cards in stack
- Collapse/expand stack

### 5.3 Drag & Drop Polish
- Auto-scroll when dragging near edges
- Visual feedback during drag
- Animation on drop

---

## File Structure

```
src/
├── modules/
│   └── main/
│       ├── sections/
│       │   ├── BoardView.vue          # NEW
│       │   ├── MainWrapper.vue        # MODIFY
│       │   └── View.vue
│       └── partials/
│           └── board/
│               ├── BoardStack.vue     # NEW
│               ├── BoardCard.vue      # NEW
│               ├── BoardCardBadges.vue # NEW
│               └── variables.scss     # NEW
├── modules/
│   └── modals/
│       └── ViewSettings.vue           # MODIFY
└── store/
    └── data.js                        # MODIFY

lib/
├── Db/
│   └── View.php                       # MODIFY
├── Migration/
│   └── Version001100Date20251228000000.php  # NEW
└── Service/
    └── ViewService.php                # MODIFY
```

---

## Implementation Order

1. **Backend first:**
   - [ ] Create migration for new columns
   - [ ] Update View entity
   - [ ] Update ViewService

2. **Basic frontend:**
   - [ ] Install vue-smooth-dnd
   - [ ] Create BoardView.vue (static)
   - [ ] Create BoardStack.vue (static)
   - [ ] Create BoardCard.vue (static)

3. **Integration:**
   - [ ] Update ViewSettings.vue with view type selector
   - [ ] Update MainWrapper.vue to render BoardView
   - [ ] Connect to real data

4. **Drag & drop:**
   - [ ] Implement card drag between stacks
   - [ ] Update row data on drop
   - [ ] Add visual feedback

5. **Polish:**
   - [ ] Styling matching Deck design
   - [ ] Responsive behavior
   - [ ] Empty state handling

---

## Testing Checklist

- [ ] Create board view from table
- [ ] Cards grouped correctly by selection column
- [ ] Drag card to different stack updates row
- [ ] Click card opens edit modal
- [ ] New rows appear in correct stack
- [ ] Deleted rows removed from board
- [ ] Filter/sort still works
- [ ] Permissions respected (view-only users can't drag)
