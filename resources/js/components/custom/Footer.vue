<script lang="ts" setup>
import { linkDict } from '@/composables/useLinkDictionary';
</script>

<template>
	<footer class="max-container text-mine-shaft-600 p-4 text-center text-sm">
		<p>
			Rattlesnake Mountain is a horse art roleplay game by
			<a href=" {{ linkDict.OWNER.path }}">
				{{ linkDict.OWNER.label }}</a
			>
		</p>
		<p>Copyright © Siat-s {{ new Date().getFullYear() }}</p>
		<p>Website by <a href="#">Abature Studio</a></p>
	</footer>
</template>
