<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import markdownit from 'markdown-it';
import { computed, ref } from 'vue';
import { Edit2, Save, X } from 'lucide-vue-next';

interface ProfileUser {
	id: number;
	name: string;
	bio?: string | null;
	avatar?: string | null;
}

interface Props {
	user: ProfileUser;
	herdCount: number;
	horseCount: number;
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth.user as User | null);
const isOwnProfile = computed(() => currentUser.value?.id === props.user.id);
const { getInitials } = useInitials();

const isEditing = ref(false);
const md = markdownit();

const form = useForm({
	bio: props.user.bio || '',
});

const renderedBio = computed(() => {
	if (!props.user.bio) {
		return '';
	}
	return md.render(props.user.bio);
});

const startEditing = () => {
	isEditing.value = true;
	form.bio = props.user.bio || '';
};

const cancelEditing = () => {
	isEditing.value = false;
	form.bio = props.user.bio || '';
	form.clearErrors();
};

const saveBio = () => {
	form.patch(route('profile.bio.update'), {
		preserveScroll: true,
		onSuccess: () => {
			isEditing.value = false;
		},
	});
};

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Users',
		href: '/',
	},
	{
		title: props.user.name,
		href: `/u/${props.user.id}`,
	},
];
</script>

<template>
	<Head :title="`${props.user.name}'s Profile`" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-4xl space-y-6">
			<!-- User Header -->
			<div class="text-center">
				<Avatar class="mx-auto mb-4 h-24 w-24">
					<AvatarImage
						v-if="props.user.avatar"
						:src="props.user.avatar"
						:alt="props.user.name" />
					<AvatarFallback class="bg-gray-200 text-gray-600 text-2xl font-bold">
						{{ getInitials(props.user.name) }}
					</AvatarFallback>
				</Avatar>
				<h1 class="text-3xl font-bold">{{ props.user.name }}</h1>
				<p class="mt-2 text-gray-600">
					Rattlesnake Mountain Player
				</p>
			</div>

			<!-- Collection Stats -->
			<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
				<Card>
					<CardHeader>
						<CardTitle
							class="flex items-center justify-between">
							<span>Herd Collection</span>
							<span class="text-2xl font-bold">{{
								props.herdCount
							}}</span>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<p class="mb-4 text-gray-600">
							View {{ props.user.name }}'s collection of
							herds and their horses.
						</p>
						<Link
							:href="route('users.herds', props.user.id)"
							class="block">
							<Button class="w-full">View Herds</Button>
						</Link>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle
							class="flex items-center justify-between">
							<span>Horse Collection</span>
							<span class="text-2xl font-bold">{{
								props.horseCount
							}}</span>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<p class="mb-4 text-gray-600">
							Browse {{ props.user.name }}'s individual
							horses and their details.
						</p>
						<Link
							:href="route('users.horses', props.user.id)"
							class="block">
							<Button class="w-full">View Horses</Button>
						</Link>
					</CardContent>
				</Card>
			</div>

			<!-- Info -->
			<Card>
				<CardHeader>
					<CardTitle class="flex items-center justify-between">
						<span>Info</span>
						<Button
							v-if="isOwnProfile && !isEditing"
							variant="ghost"
							size="sm"
							@click="startEditing">
							<Edit2 class="mr-2 h-4 w-4" />
							Edit
						</Button>
					</CardTitle>
				</CardHeader>
				<CardContent>
					<div v-if="isEditing && isOwnProfile" class="space-y-4">
						<textarea
							v-model="form.bio"
							rows="8"
							placeholder="Write about yourself... (Markdown supported, URLs not allowed)"
							class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-[200px] flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />
						<div
							v-if="form.errors.bio"
							class="text-sm text-red-600">
							{{ form.errors.bio }}
						</div>
						<div class="flex justify-end gap-2">
							<Button
								size="sm"
								:disabled="form.processing"
								@click="saveBio">
								<Save class="mr-2 h-4 w-4" />
								Save
							</Button>
							<Button
								size="sm"
								variant="outline"
								:disabled="form.processing"
								@click="cancelEditing">
								<X class="mr-2 h-4 w-4" />
								Cancel
							</Button>
						</div>
					</div>
					<div
						v-else
						class="prose prose-sm max-w-none dark:prose-invert">
						<div
							v-if="renderedBio"
							v-html="renderedBio"></div>
						<p
							v-else-if="isOwnProfile"
							class="text-gray-500 italic">
							No bio yet. Click Edit to add one.
						</p>
						<p
							v-else
							class="text-gray-500 italic">
							No bio available.
						</p>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
