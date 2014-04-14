This console program parse products information (title, desc, cost..) from online catalogs, shops, etc.
Currently implemented parsing for http://rozetka.com.ua, but architecture gives flexibility to add parsers for different sites by extending needful interfaces and base classes.
Program build on top of symfony2 components.

Basic architectural idea is - implement parsing as chain of parse units.
Each parse unit is a class, which can parse small piece of information and collect this information in shared context.
This provides scalability, testability and a more clear code.
You can find unit tests in Test folders.

Folders:

ParserChain - parser chain library with interfaces and simple chain implementation.
ParserChain/Tests - unit tests for parser chain
CatalogParser - main part of program.
CatalogParser/Parser/Rozetka - parser implementation for rozetka.com.ua
CatalogParser/Tests - unit tests for catalog parser

Entry Point:

RozetkaCommand.php