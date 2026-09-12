/**
 * Shared shape and class logic for CMS content boxes.
 *
 * `boxClasses` is imported by both the read-only renderer (DynamicInfo) and the
 * inline editor so edit mode paints the exact same box as the published page.
 * Do not fork it.
 */

export type CmsBoxWidth = 'third' | 'half' | 'two-thirds' | 'full';

export type CmsBoxStyle = 'box' | 'box-alt' | 'box-centered' | 'band';

export interface CmsBox {
	id: string;
	width: CmsBoxWidth;
	style: CmsBoxStyle;
	html: string;
	/** Present only on the home page's pinned News slot, whose html is empty. */
	kind?: 'news';
}

export interface CmsAnnouncement {
	id: number;
	title: string;
	body: string;
	published_at: string | null;
}

/** Widths on the lg:grid-cols-6 grid. Below lg every box stacks. */
export const widthClasses: Record<CmsBoxWidth, string> = {
	third: 'lg:col-span-2',
	half: 'lg:col-span-3',
	'two-thirds': 'lg:col-span-4',
	full: 'lg:col-span-6',
};

export const widthOptions: { value: CmsBoxWidth; label: string }[] = [
	{ value: 'third', label: '1/3' },
	{ value: 'half', label: '1/2' },
	{ value: 'two-thirds', label: '2/3' },
	{ value: 'full', label: 'Full' },
];

export const styleOptions: { value: CmsBoxStyle; label: string }[] = [
	{ value: 'box', label: 'Box' },
	{ value: 'box-alt', label: 'Box (alt)' },
	{ value: 'box-centered', label: 'Box (centered)' },
	{ value: 'band', label: 'Band' },
];

export function boxClasses(box: CmsBox): string[] {
	// space-y-2 stands in for the per-block wrapper divs the markdown
	// renderer used to emit: without it the paragraphs inside a box collide.
	const classes: string[] = ['space-y-2'];
	if (box.style === 'band') {
		// Band cards sit in the darker full-bleed strip, styled like the
		// old landing link cards.
		classes.push('box-alt', 'cms-band', 'text-center');
	} else {
		classes.push(box.style === 'box-alt' ? 'box-alt' : 'box');
		if (box.style === 'box-centered') classes.push('text-center');
	}
	classes.push(widthClasses[box.width] ?? widthClasses.full);
	return classes;
}

export type BoxSegment = { band: boolean; boxes: CmsBox[] };

/**
 * Split the box list into runs: adjacent band boxes share one full-bleed
 * strip, everything else sits in the max-container grid between them.
 */
export function segmentBoxes(boxes: CmsBox[]): BoxSegment[] {
	const segments: BoxSegment[] = [];
	for (const box of boxes) {
		const band = box.style === 'band';
		const last = segments[segments.length - 1];
		if (last && last.band === band) {
			last.boxes.push(box);
		} else {
			segments.push({ band, boxes: [box] });
		}
	}
	return segments;
}

/**
 * Client-generated id for a new box. The server rewrites a blank id to a
 * positional `b{n}`, which can collide with an id an existing box already
 * holds, so every new box must arrive with its own id.
 */
export function createBoxId(): string {
	if (
		typeof crypto !== 'undefined' &&
		typeof crypto.randomUUID === 'function'
	) {
		return `b-${crypto.randomUUID()}`;
	}
	return `b-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

/** Never creates a News slot: that box only exists on home, server-side. */
export function createBox(): CmsBox {
	return {
		id: createBoxId(),
		width: 'third',
		style: 'box',
		html: '<p></p>',
	};
}
