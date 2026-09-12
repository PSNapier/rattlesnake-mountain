/**
 * Shared shape and class logic for CMS content boxes.
 *
 * `boxClasses` is imported by both the read-only renderer (DynamicInfo) and the
 * inline editor so edit mode paints the exact same box as the published page.
 * Do not fork it.
 */

export interface CmsBox {
	id: string;
	span: 1 | 2 | 3;
	style: 'box' | 'box-alt' | 'box-centered';
	html: string;
}

export const spanClasses: Record<CmsBox['span'], string> = {
	1: 'lg:col-span-1',
	2: 'lg:col-span-2',
	3: 'lg:col-span-3',
};

export function boxClasses(box: CmsBox): string[] {
	// space-y-2 stands in for the per-block wrapper divs the markdown
	// renderer used to emit: without it the paragraphs inside a box collide.
	const classes: string[] = ['space-y-2'];
	classes.push(box.style === 'box-alt' ? 'box-alt' : 'box');
	if (box.style === 'box-centered') classes.push('text-center');
	classes.push(spanClasses[box.span]);
	return classes;
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

export function createBox(): CmsBox {
	return {
		id: createBoxId(),
		span: 1,
		style: 'box',
		html: '<p></p>',
	};
}
