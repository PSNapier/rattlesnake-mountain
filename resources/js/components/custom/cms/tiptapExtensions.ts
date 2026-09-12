import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';

/**
 * The server sanitises saved HTML against this allowlist:
 *
 *   strong, em, h1-h4, ul, ol, li, a[href,target,rel,title],
 *   img[src,alt,title,width,height], blockquote, hr, p, br
 *
 * Anything the editor can produce outside that list is silently eaten on save,
 * which reads as data loss to the admin. So the editor is configured to be
 * incapable of producing it in the first place: no code, no code blocks, no
 * strikethrough, no underline, no headings past level 4.
 *
 * StarterKit v3 bundles the link extension, so `@tiptap/extension-link` is not
 * imported here — configuring it twice would register the mark twice.
 */
export function cmsExtensions() {
	return [
		StarterKit.configure({
			code: false,
			codeBlock: false,
			strike: false,
			underline: false,
			heading: {
				levels: [
					1,
					2,
					3,
					4,
				],
			},
			link: {
				openOnClick: false,
				autolink: false,
				HTMLAttributes: {
					rel: 'noopener noreferrer',
					target: '_blank',
				},
			},
		}),
		Image.configure({
			inline: false,
			allowBase64: false,
		}),
	];
}
