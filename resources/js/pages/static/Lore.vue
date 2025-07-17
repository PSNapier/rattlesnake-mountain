<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Lore',
	description: 'The history of our world and how it came to be.',
};
const images = [
	{
		name: 'Nikkayla',
		link: 'https://www.deviantart.com/nikkayla',
		path: '/images/lore-art-Nikkayla.png',
	},
];
const content = {
	box1: [
		`![](/images/lore-banner-scene.png)`,
		`**PROLOGUE.**\n\nLorekeeper: [Lady](${linkDict.LADY.path})`,
		`*They whisper of a time, many hundreds of years ago, when we were not endlessly at war with the desert; when every day was not a struggle to exist and our families were safe from the elements in wooden boxes. Our only trouble in the world of man was when a meal came an hour late, and we never had to worry about wolves getting our children at night. But men fail; their buildings burn and they die, their children failing to take up the mantle of their forefathers. And we were turned loose, either by chance or by fate. I remember, clear as day, my father's story - passed down for generation upon generation - of our lineage and how we became free.*`,
		`*He would say, "On the eve of our family's Great Sorrow, a terrible storm brewed and fire rained down from the sky. We screamed in terror, calling for the men who had never failed to protect us before. But that night, when we needed them the most, they were as silent as the dead. Wild with fear and betrayal, we broke from our wooden boxes and ran into the fields we had plowed. Foals struggled to keep from being trampled among the panic; in those first hours, it was every horse for themselves.*`,
		`*When the sun rose again, our home of water, grass, and sunlight was covered with ash. Some of us suffered from savage burns on our necks, faces, and backs. Another stallion and I investigated the remains of our smoking home, stupid enough to hope we could return to the life we'd always known. But bits of our stable still glowed and radiated with heat. Man - or something more powerful - had turned our sanctuary into something ugly and unrecognizable.*`,
		`*And though we felt it deep in our hearts, we were not alone.*`,
		`*The wild horses - those who had met freedom years before - did not take kindly to our arrival. They seemed content to watch at first, perhaps mocking us for what we did not yet know. Even when we did encounter them, the skirmishes were brief and often ended with the wild stallion taking our mares or driving us away. They laughed at us, knowing that we did not belong. Not yet.*`,
		`*But, many long years later, we have learned how to be strong and we have proven that we can survive in the natives' cruel world. More than that, we have thrived. It is now our your time to take this range in their hooves and shape their lives as they will. I am old and tired, burdened by memories I'd rather not have, and it is my privilege to stand back and watch what they make."*`,
		`*So, fight your battles, take whatever you can keep, and prove that you can handle anything the world throws at you. But take it from someone who knows... it will be a painful journey.*`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
