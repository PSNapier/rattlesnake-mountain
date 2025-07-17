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
	// lorekeepers
	LADY: makeLink(
		'Lady',
		'https://www.deviantart.com/rattlesnakeadmin/art/RATTLESNAKE-MOUNTAIN-Mare-004-The-Lorekeeper-813634467',
	),
	// misc links
	OWNER: makeLink('Siat-s', 'https://siat-s.deviantart.com'),
	DISCORD: makeLink('Discord', 'https://discord.gg/rArZNnkCfE'),
	LINEART: makeLink(
		'Lineart',
		'https://www.deviantart.com/rattlesnakeadmin/art/RESOURCE-Lineart-Bases-812737644',
	),
	ADMIN: makeLink('Admin', 'https://www.deviantart.com/rattlesnakeadmin'),
	DESIGN_RULES: makeLink(
		'Design Rules',
		'https://www.deviantart.com/rattlesnakeadmin/gallery/83931899/design-guide',
	),
	DRAFT_BREED_REF: makeLink(
		'Draft Breed Reference',
		'https://sites.google.com/site/vadhma/draft-breeds-and-definitions',
	),
	MAP: makeLink(
		'Map',
		'https://www.deviantart.com/rattlesnakeadmin/art/RESOURCE-Map-813626388',
	),
	CLAIMABLE: makeLink(
		'Claimable',
		'https://www.deviantart.com/rattlesnakeadmin/gallery?q=claimable',
	),
	STATS_REF: makeLink(
		'Stats Explained',
		'https://imgur.com/d-d-stats-explained-WzhSq42',
	),
	BENE_DETS: makeLink(
		'Benefits & Detriments',
		'https://docs.google.com/spreadsheets/d/1MdMQyHTcjdlbWutxdydxLqXIzEiRysWTnErXblHKHik/edit?gid=0#gid=0',
	),
	SUBMISSION_VID: makeLink(
		'Submission Vid',
		'https://drive.google.com/file/d/1S3VTPAJeEqEllVkQI-Eifcr1fRLiLr1s/view',
	),
	EXAMPLE_CHAR: makeLink(
		'Example Character',
		'https://www.deviantart.com/rattlesnakeadmin/art/RATTLESNAKE-MOUNTAIN-Stallion-B-0021-Umbreon-919282970',
	),
	// rollers
	ROLLER_STORY_PROG: makeLink(
		'Story Progression Roller',
		'https://docs.google.com/spreadsheets/d/1noH5IwE7Zx5lBKSJco8bLbonwHDWqoDEdPqzjvJ3HiA/edit?usp=sharing',
	),
};
