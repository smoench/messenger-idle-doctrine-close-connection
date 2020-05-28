# messenger-idle-doctrine-close-connection

The combination of `DoctrinePingConnectionMiddleware` and `DoctrineCloseConnectionMiddleware` on a queue with periodically incoming thousands of messages at the same time will results to heavy I/O operations. This subscriber will only close the connection when the worker is in idle mode insteadof close the connection after each message.
