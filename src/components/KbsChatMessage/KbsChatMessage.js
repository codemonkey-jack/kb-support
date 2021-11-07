import createDOMPurify from "dompurify";
import KbsCustomerReply from "./KbsCustomerReply";
import KbsNote from "./KbsNote";
import KbsAgentReply from "./KbsAgentReply";

const DOMPurify = createDOMPurify();

const KbsChatMessage = (props) => {
	const { message, deleteReply, setNewReply, id } = props;
	// convert to readeable date month date year

	const date = new Date(message.date);
	const dateString = `${date.toLocaleString("en-US", {
		month: "short",
		day: "numeric",
		year: "numeric",
		hour: "numeric",
		minute: "numeric",
		hour12: true,
	})}`;

	if (message.isAgent === false) {
		return (
			<KbsCustomerReply
				message={message}
				deleteReply={deleteReply}
				setNewReply={setNewReply}
				id={id}
				DOMPurify={DOMPurify}
				dateString={dateString}
			/>
		);
	} else if (message.isAgent === true && "kbs_ticket_reply" == message.type) {
		return (
			<KbsAgentReply
				message={message}
				deleteReply={deleteReply}
				setNewReply={setNewReply}
				id={id}
				DOMPurify={DOMPurify}
				dateString={dateString}
			/>
		);
	} else if ("kbs_ticket_note" == message.type) {
		return (
			<KbsNote
				message={message}
				deleteReply={deleteReply}
				setNewReply={setNewReply}
				id={id}
				DOMPurify={DOMPurify}
				dateString={dateString}
			/>
		);
	} else {
		return <div></div>;
	}
};

export default KbsChatMessage;
