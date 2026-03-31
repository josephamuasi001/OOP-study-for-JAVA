

while True:
    print("MTN MOBILE MONEY")
    print("1. Send Money")
    print("2. Receive Money")
    print("3. Check Balance")
    print("4. Exit")

    choice = input("Enter your choice: ")
    if choice == '1':
        amount = input("Enter the amount to send: ")
        recipient = input("Enter the recipient's phone number: ")
        print(f"Sending {amount} to {recipient}...")
        print("Transaction successful!")
    elif choice == '2':
        sender = input("Enter the sender's phone number: ")
        amount = input("Enter the amount received: ")
        print(f"Received {amount} from {sender}.")
    elif choice == '3':
        print("Your current balance is: 1000 UGX")
    elif choice == '4':
        print("Exiting...")
        break
    else:
        print("Invalid choice. Please try again.")


//Just a simple simulation of a mobile money service. In a real application, you would need to implement security measures, error handling, and connect to a backend service to manage transactions and balances.
// I will be doing a project on mobile money and this is a simple code to simulate the basic functionalities of a mobile money service. You can expand on this code by adding more features such as transaction history, user authentication, and integration with a database to store user information and transaction records.