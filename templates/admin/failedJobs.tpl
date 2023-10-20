{**
 * templates/admin/failedJobs.tpl
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Failed Jobs index
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{translate key=$pageTitle}
	</h1>
	<div class="app__contentPanel">
		<pkp-table
			:columns="columns"
			:rows="rows"
			:description="description"
			:label="label"
		>
			<template #label v-if="total > 0">
				<div>
					<span class="pkp_helpers_half pkp_helpers_align_left">{{ label }}</span>
					<span class="pkp_helpers_half pkp_helpers_align_right pkp_helpers_text_right">
					<spinner v-if="isLoadingItems"></spinner>
						<pkp-button @click="requeueAll">
							{translate key="admin.jobs.failed.action.redispatch.all"}
						</pkp-button>
					</span>
				</div>
			</template>

			<template #default="{ row, rowIndex }">
				<table-cell
					v-for="(column, columnIndex) in columns"
					:key="column.name"
					:column="column"
					:row="row"
					:tabindex="!rowIndex && !columnIndex ? 0 : -1"
				>
					<template #default v-if="column.name === 'actions'">
						<button-row >
							<pkp-button @click="redispatch(row)">
								{translate key="admin.jobs.failed.action.redispatch"}
							</pkp-button>
							<pkp-button is-warnable @click="remove(row)">
								{translate key="common.delete"}
							</pkp-button>
							<pkp-button element="a" is-link :href="row._hrefs._details">
								{translate key="common.details"}
							</pkp-button>
						</button-row>
					</template>
                </table-cell>
			</template>
		</pkp-table>

		<pagination 
			v-if="lastPage > 1"
			:current-page="currentPage"
			:last-page="lastPage"
			:is-loading="isLoadingItems"
			@set-page="handlePagination"
		/>
	</div>
{/block}
