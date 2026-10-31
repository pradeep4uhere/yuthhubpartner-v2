import EventTransactionList from "./EventTransactionList.jsx";

/**
 * Displays all Event Transactions accessible to the Placement Partner.
 */
export default function EventTransactions() {
    return (
        <EventTransactionList
            title="All Event Transactions"
            showHeader={true}
        />
    );
}
