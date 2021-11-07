import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";
const { createHigherOrderComponent } = wp.compose;

const richTextModules = {
	toolbar: [
		[{ header: [1, 2, false] }],
		["bold", "italic", "underline"],
		["code-block"],
		[
			{ list: "ordered" },
			{ list: "bullet" },
			{ indent: "-1" },
			{ indent: "+1" },
		],
		["link", "image"],
	],
};
const richTextModulesFilter = wp.components.withFilters(
	"kbs.kbsRichTextModules"
)(richTextModules);

const withCannedRepliesModules = createHigherOrderComponent(
	(richTextModules) => {
		console.log(richTextModules);
		return (props) => {
			console.log(props);
			return 0;
		};
	},
	"withCannedRepliesModules"
);
wp.hooks.addFilter(
	"kbs.kbsRichTextModules",
	"kbs/kbsRichText-Modules",
	withCannedRepliesModules,
	1
);

const KbsRichText = (props) => {
	const { newReply, setNewReply } = props;
	const modules = {
		toolbar: [
			[{ header: [1, 2, false] }],
			["bold", "italic", "underline"],
			["code-block"],
			[
				{ list: "ordered" },
				{ list: "bullet" },
				{ indent: "-1" },
				{ indent: "+1" },
			],
			["link", "image"],
		],
	};

	const formats = [
		"header",
		"bold",
		"italic",
		"underline",
		"strike",
		"blockquote",
		"code-block",
		"list",
		"bullet",
		"indent",
		"link",
		"image",
	];
	return (
		<>
			<ReactQuill
				className="w-full text-editor"
				theme="snow"
				modules={modules}
				formats={formats}
				value={newReply}
				onChange={(e) => {
					setNewReply(e);
				}}
				onFocus={(range, source, editor) => {
					console.log(range);
				}}
			/>
		</>
	);
};

export default KbsRichText;
