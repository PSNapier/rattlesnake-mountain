<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
import { linkDict } from '@/composables/useLinkDictionary';
</script>

<template>
	<Layout title="Home">
		<template #hero>
			<h1>Rattlesnake Mountain</h1>
			<p>Lore page.</p>
		</template>

		<!-- Main content for this page -->
		<div class="max-container grid grid-cols-1 gap-4 p-4 lg:grid-cols-3">
			<div class="box lg:col-span-2">
				<img
					src="/images/lore-banner-scene.png"
					alt="Scene Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p><strong>PROLOGUE.</strong></p>
					<p>
						Lorekeeper:&nbsp;
						<a :href="linkDict.LADY.path">Lady</a>
					</p>
					<br />
					<p class="italic">
						They whisper of a time, many hundreds of years
						ago, when we were not endlessly at war with the
						desert; when every day was not a struggle to exist
						and our families were safe from the elements in
						wooden boxes. Our only trouble in the world of man
						was when a meal came an hour late, and we never
						had to worry about wolves getting our children at
						night. But men fail; their buildings burn and they
						die, their children failing to take up the mantle
						of their forefathers. And we were turned loose,
						either by chance or by fate. I remember, clear as
						day, my father's story - passed down for
						generation upon generation - of our lineage and
						how we became free.
					</p>
					<br />
					<p class="italic">
						He would say, "On the eve of our family's Great
						Sorrow, a terrible storm brewed and fire rained
						down from the sky. We screamed in terror, calling
						for the men who had never failed to protect us
						before. But that night, when we needed them the
						most, they were as silent as the dead. Wild with
						fear and betrayal, we broke from our wooden boxes
						and ran into the fields we had plowed. Foals
						struggled to keep from being trampled among the
						panic; in those first hours, it was every horse
						for themselves.
					</p>
					<br />
					<p class="italic">
						When the sun rose again, our home of water, grass,
						and sunlight was covered with ash. Some of us
						suffered from savage burns on our necks, faces,
						and backs. Another stallion and I investigated the
						remains of our smoking home, stupid enough to hope
						we could return to the life we'd always known. But
						bits of our stable still glowed and radiated with
						heat. Man - or something more powerful - had
						turned our sanctuary into something ugly and
						unrecognizable.
					</p>
					<br />
					<p class="italic">
						And though we felt it deep in our hearts, we were
						not alone.
					</p>
					<br />
					<p class="italic">
						The wild horses - those who had met freedom years
						before - did not take kindly to our arrival. They
						seemed content to watch at first, perhaps mocking
						us for what we did not yet know. Even when we did
						encounter them, the skirmishes were brief and
						often ended with the wild stallion taking our
						mares or driving us away. They laughed at us,
						knowing that we did not belong. Not yet.
					</p>
					<br />
					<p class="italic">
						But, many long years later, we have learned how to
						be strong and we have proven that we can survive
						in the natives' cruel world. More than that, we
						have
					</p>
				</div>
			</div>

			<div class="lg:row-span-2">
				<div class="box">
					<img
						src="/images/lore-art-nikkayla.png"
						alt="Art by Nikkayla"
						class="h-auto w-full rounded-xl" />
				</div>
				<div class="mt-2 text-center text-sm">
					Art by
					<a
						href="https://www.deviantart.com/nikkayla"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-400 underline">
						@Nikkayla
					</a>
				</div>
			</div>
		</div>
	</Layout>
</template>
