import styles from "../../styles.css";
import { Menu, Transition } from "@headlessui/react";
import { Fragment } from "react";
export default function KbsChatMessageToolbar(props) {
	const { reply, id, deleteReply, setNewReply } = props;
	const { isAgent, type } = reply;
	return (
		<Menu as="div" className="relative hidden text-left kbs-chat-toolbar">
			<div>
				<Menu.Button className="inline-flex justify-center w-full px-2 py-1 text-sm font-medium text-gray-700 bg-transparent border rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500">
					:
				</Menu.Button>
			</div>

			<Transition
				as={Fragment}
				enter="transition ease-out duration-100"
				enterFrom="transform opacity-0 scale-95"
				enterTo="transform opacity-100 scale-100"
				leave="transition ease-in duration-75"
				leaveFrom="transform opacity-100 scale-100"
				leaveTo="transform opacity-0 scale-95"
			>
				<Menu.Items className={`absolute ${ 'kbs_ticket_note' == type || isAgent  ? "right-0" : "left-0" } z-50 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none`}>
					<div className="py-1">
						<Menu.Item>
							{({ active }) => (
								<a
									href="#"
									className={
										(active
											? "bg-gray-100 text-gray-900"
											: "text-gray-700",
										"block px-4 py-2 text-sm")
									}
									onClick={(e) =>
										deleteReply(
											e,
											id,
											reply.id,
											setNewReply
										)
									}
								>
									Delete
								</a>
							)}
						</Menu.Item>
					</div>
				</Menu.Items>
			</Transition>
		</Menu>
	);
}
