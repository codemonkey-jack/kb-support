import index from "../styles.css";
import React, { useState, useEffect, Fragment } from "react";
import KbsChatMessage from "./KbsChatMessage/KbsChatMessage";
import KbsRichText from "./KbsRichText";

const App = (props) => {
	const [combinedReplies, setCombinedReplies] = useState(false);
	const [id, setId] = useState(0);
	const [newReply, setNewReply] = useState("");
	const [newReplySubmitted, setNewReplySubmitted] = useState(false);
	const [submitButton, setSubmitButton] = useState(false);
	const [initialReplies, setInitialReplies] = useState(0);

	// Await response from fetch request
	const getReplies = async (id) => {
		const response = await wp.apiFetch({
			path: `kbs/v1/replies/ticket/${id}?per_page=100`,
		});
		let data = await response;
		data = response.map((reply) => {
			let updatedValue = reply.content.raw;
			return { ...reply, content: updatedValue };
		});
		return data;
	};

	const getNotes = async (id) => {
		const response = await wp.apiFetch({
			path: `kbs/v1/replies/ticket/notes/${id}?per_page=100`,
		});
		let data = await response;
		return data;
	};

	const concatReplies = async (id) => {
		const replies = await getReplies(id);
		const notes = await getNotes(id);
		let combinedReplies = [...notes, ...replies];
		combinedReplies = combinedReplies.sort(function (a, b) {
			let c, d;
			c = new Date(a.date);
			d = new Date(b.date);
			return c - d;
		});
		if( initialReplies === 0 || initialReplies !== combinedReplies.length ) {
			setInitialReplies(combinedReplies.length);
		}
		setNewReplySubmitted(false);
		setCombinedReplies(combinedReplies);
	};
	useEffect(() => {
		setId(window.location.href.match(/([post=][0-9])\w+/g)[0].slice(1));
		concatReplies(id);
	}, [id, newReplySubmitted, initialReplies]);

	const handleSubmit = (e) => {
		e.preventDefault();

		switch (submitButton) {
			case "reply":
				jQuery
					.ajax({
						url: kbApiSettings.root + `kbs/v1/replies/ticket/${id}`,
						method: "POST",
						data: {
							id: id,
							reply_content: newReply,
						},
					})
					.done(function (response) {
						setNewReplySubmitted(true);
					});

				setNewReply("");

				break;
			case "note":
				jQuery
					.ajax({
						url:
							kbApiSettings.root +
							`kbs/v1/replies/ticket/notes/${id}`,
						method: "POST",
						data: {
							id: id,
							note: newReply,
						},
					})
					.done(function (response) {
						setNewReplySubmitted(true);
					});

				setNewReply("");
				break;
		}
	};

	const deleteReply = (e, id, replyId) => {
		e.preventDefault();
		fetch(`${kbApiSettings.root}kbs/v1/replies/${replyId}`, {
			method: "DELETE",
			data: { id: replyId },
		}).then((response) => {
			setNewReplySubmitted(true);
		});
		setNewReply("");
	};

	if (combinedReplies != false) {
		return (
			<div className="container p-1 bg-gray-100">
				{combinedReplies.map((value) => {
					return (
						<KbsChatMessage
							message={value}
							id={id}
							deleteReply={deleteReply}
							setNewReply={setNewReply}
						/>
					);
				})}
				<form
					className="flex flex-col w-full bg-white"
					onSubmit={handleSubmit}
				>
					<KbsRichText
						newReply={newReply}
						setNewReply={setNewReply}
					/>
					<button
						className="p-3 font-bold bg-green-200"
						type="submit"
						disabled={!newReply}
						onClick={() => setSubmitButton("reply")}
					>
						Send Reply
					</button>
					<button
						className="p-3 bg-yellow-200"
						type="submit"
						disabled={!newReply}
						onClick={() => setSubmitButton("note")}
					>
						Send Note
					</button>
				</form>
			</div>
		);
	}
	return "Loading";
};

export default App;
