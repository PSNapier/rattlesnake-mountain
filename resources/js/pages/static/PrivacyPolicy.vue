<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

const hero = {
	title: 'Privacy Policy',
	description: 'How we handle your data',
};
const images = [
	{
		name: 'Nikkayla',
		link: 'https://www.deviantart.com/Nikkayla',
		path: '/images/lore-art-Nikkayla.png',
	},
];
const content = {
	box1: [
		`# Data We Collect`,
		`We collect the following information:`,
		`- Name and email address (for account creation)`,
		`- Character images you upload`,
		`- Herd and horse data you create`,
		`- Session cookies (essential for site functionality)`,
	],
	box2: [
		`# Why We Collect It`,
		`We collect this data to:`,
		`- Provide you with account functionality`,
		`- Store your game data (herds, horses, character images)`,
		`- Maintain your session while using the site`,
	],
	box3: [
		`# Data Retention`,
		`- Account data: Retained until you delete your account`,
		`- Character images: Soft-deleted items are permanently removed after 30 days`,
		`- Logs: Retained for 14 days (warning level and above only)`,
	],
	box4: [
		`# Data Sharing`,
		`We do not share your personal data with third parties for marketing purposes.`,
		`We use Mailgun (a third-party email service) to send transactional emails such as account verification and password resets. Mailgun processes your email address only for the purpose of delivering these emails. You can review Mailgun's privacy policy at [mailgun.com/legal/privacy-policy](https://www.mailgun.com/legal/privacy-policy).`,
	],
	box5: [
		`# Your Rights`,
		`You have the right to:`,
		`- Access your data (view your account settings)`,
		`- Delete your data (delete your account from settings)`,
		`- Request a copy of your data (contact us)`,
	],
	box6: [
		`# Contact`,
		`For privacy-related inquiries, contact us at:`,
		`[abaturestudio@gmail.com](mailto:abaturestudio@gmail.com?subject=Privacy%20Inquiry)`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
