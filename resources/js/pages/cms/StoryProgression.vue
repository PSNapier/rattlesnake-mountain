<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: `Story Progression`,
	description: `On adventuring.`,
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
		`![](/images/storyprog-banner-storyprog.png)`,
		`*Each submission, be it literature or art, continues your herd's story. There are three main ways to progress your herd's story: story arcs, which are events rolled to start a new journey or during a journey; checkpoints, or the conclusion of a story arc; and game-wide events.*`,
	],
	box2: [
		`![](/images/storyprog-banner-storyarcs.png)`,
		`*Story arcs are rolled each time your herd begins to travel to a new location and are continuously rolled along the way. Story arcs are tailored to fit your herd, and can be as simple and easy as finding a herdless horse or picking up a Stone; or as complex and dangerous as happening upon a starving mother cougar and her cub.*`,
		`a. Story arcs are rolled for using the [Story Progression](${linkDict.ROLLER_STORY_PROG.path}) roller.\n\nb. Three herd members receive event rolls; all others are eligible for Item rolls, healing rolls, breeding rolls, and individual bond rolls (where applicable).\n- Rolls can be affected by Benefits and Detriments.\n\nc. To continue a story arc, please your next entry to your herd's thread with the form found in our #herd-stories channel filled out.`,
	],
	box3: [
		`![](/images/storyprog-banner-checkpoints.png)`,
		`*Checkpoints are a save point for your herd's story, and allow you to work on the many passive attributes of your herd in a relatively safe location. Checkpoints are immediately available upon reaching a new location.*`,
		` a. Once your herd has reached a checkpoint, they will have an enhanced opportunity to heal, work on herd relationships, birth foals during the correct season, etc.\n- Checkpoints increase all healing, claiming, herd unity building efforts, etc by 20%.\n- Checkpoints decrease the chance of negative-action rolls (foul weather, aggressive horse, human, predator, etc) by 50%. Staying at a Checkpoint for too long, however (~5 Checkpoint submissions), will result in negative-action rolls increasing by 25%.\n\nb. Checkpoints may also be requested during travel, though all effects will be considerably diminished and, by lingering in one place too long, increase the likelihood of encountering danger.\n- To request a Checkpoint during travel, include it in the submission form. These Checkpoints can be important for recovering health or restoring herd unity in emergency situations.\n- Effectiveness of all healing, claiming, herd unity building efforts, etc are decreased by 20%.\n- Travel Checkpoints increase the change of encountering an aggressive horse, human, or predator by 50%.\n\nc. To end the Checkpoint and have a fresh story arc rolled, please post your next response in your herd's thread on our Discord with the appropriate form filled out.\n- In this entry, include the destination your herd is traveling to. This may be changed at any point during the story arcs that follow.\n- You may also include character goals for the new story arc in this submission. This will help our storytellers create your next arc!`,
	],
	box4: [
		`![](/images/storyprog-banner-events.png)`,
		`*Game-Wide Events are events that take place in various locations across the map. These events may cause tragedy or triumph, and are often out of the player's (and storyteller's) control. They are highly dependent on weather and man-made factors, but those who make it through each event will find themselves stronger than before (and well-paid, as this is a game, after all!)​*`,
		`a. No horses are immune to some events, and some may result in the loss of your played character. These events are the main reason we ask that you not play a character that you are attached to in this game--unless you are willing to say goodbye to them unexpectedly.\n- While these events occur once or twice every in-game year and can be dangerous, that does not mean that every event will be negative! Some are good news--like the passing of legislation restricting humans from riding their ATVs in a particular part of the map.\n\nb. Many events are triggered by story arcs. The specifics of which are outlined in the Story Progression roller linked above, but there are some that are triggered by secret means and are intended as surprise or pop-up events!\n\nc. Events will always be announced in the 📢-announcements channel initially. Updates to events will be posted in the #event-announcements channel, and only players with the @Event Alerts role will be tagged.`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
