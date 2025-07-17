export type ContentBlock =
	| { type: 'md'; content: string }
	| { type: 'image'; path: string };
