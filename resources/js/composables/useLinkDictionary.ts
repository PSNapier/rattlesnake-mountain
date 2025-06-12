function makeLink(label: string, path: string) {
	return {
		label,
		path,
	};
}

export const linkDict = {
	// main links
	HOME: makeLink('Home', '/'),
	GETTING_STARTED: makeLink('Getting Started', '/getting-started'),
	WILDLIFE: makeLink('Wildlife', '/wildlife'),
	CONTACT_US: makeLink('Contact Us', '/contact-us'),
	// getting started links
	RULES: makeLink('Rules', '/rules'),
	LORE: makeLink('Lore', '/lore'),
	CHARACTER_HANDBOOK: makeLink('Character Handbook', '/character-handbook'),
	STATS_LEVELING: makeLink('Stats & Leveling', '/stats-leveling'),
	CHARACTER_UPLOAD: makeLink('Character Upload', '/character-upload'),
	SHOP: makeLink('Shop', '/shop'),
	// wildlife links
	LIFESPANS: makeLink('Lifespans', '/lifespans'),
	STORY_PROGRESSION: makeLink('Story Progression', '/story-progression'),
	CLAIMING_NPCS: makeLink('Claiming NPCs', '/claiming-npcs'),
	HERD_UNITY: makeLink('Herd Unity', '/herd-unity'),
	BREEDING_FOALING: makeLink('Breeding & Foaling', '/breeding-foaling'),
	PLAYER_VS_PLAYER: makeLink('Player vs. Player', '/player-vs-player'),
	// misc links
	OWNER: makeLink('Siat-s', 'https://siat-s.deviantart.com'),
};
