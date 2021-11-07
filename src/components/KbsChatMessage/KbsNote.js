import KbsChatMessageToolbar from "./KbsChatMessageToolbar";

const KbsNote = ({ DOMPurify,dateString, message, deleteReply, setNewReply }) => {
	const {author, id} = message;
	return (
		<div className="flex flex-col items-end justify-end min-h-full kbs-chat-message">
			<div className="flex flex-col items-end w-full my-3">
				<h2 className="mb-1 italic font-bold capitalize max-w-max w-max">{`${author} replied`}</h2>
				<div className="flex flex-row-reverse message-content">
					<p
						className={`flex flex-col items-end p-3   text-black bg-yellow-200  rounded max-w-max w-max`}
					>
						{
							<div
								className="mb-3"
								dangerouslySetInnerHTML={{
									__html: DOMPurify.sanitize(message.content),
								}}
							/>
						}
						<sub className="ml-3"> {dateString}</sub>
					</p>
					<KbsChatMessageToolbar
						reply={message}
						id={id}
						deleteReply={deleteReply}
						setNewReply={setNewReply}
					/>
				</div>
			</div>
		</div>
	);
};

export default KbsNote;
