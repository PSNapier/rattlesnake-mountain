function makeLink(label: string, path: string) {
	return {
		label,
		path,
	};
}

export const linkDict = {
	HOME: makeLink('Home', '/'),
	GETTING_STARTED: makeLink('Getting Started', '/getting-started'),
	WILDLIFE: makeLink('Wildlife', '/wildlife'),
	CONTACT_US: makeLink('Contact Us', '/contact-us'),
	OWNER: makeLink('Siat-s', 'https://siat-s.deviantart.com'),
};
