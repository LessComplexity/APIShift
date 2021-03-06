<div align="center">
<img width="100px" src="https://gitlab.com/lesscomplexity/apishift/-/raw/master/images/DevLogo.png">

# APIShift Code Design
</div>

This document organizes the different components, sub-components and their properties that make up the engine. The goal of this document is to provide both a high level overview of the architecture of the system and a guide for the specifics of functionality and algorithmics behind the engine. All new systems and components first need to be integrated in the code design before going into development stages to keep a clean, understandable flow of development, organization and efficiency. It is always better to first design the components and overall system before developing, it helps everyone to follow up with the same ideas and standards when developing and contributing to the system.

The definitions of this architecture document will follow the definitions mentioned by [Fielding, Roy Thomas. Architectural Styles and the Design of Network-based Software Architectures. Doctoral dissertation, University of California, Irvine, 2000. Chapter 1](https://www.ics.uci.edu/~fielding/pubs/dissertation/software_arch.htm).

# Architecture Overview
In this section we will give an overview of the definitions, components, the connections between them and the data elements of the architecture.

## Definitions
The system uses the following syntactic terms, which are wrapped as architectural components in our architecture. The purpose of those definitions is to create a modular connection handling when passing data elements between components, meaning that architectually (and practically) components won't need to worry if the data came in from code or database in the application.

 * __Data Entry__: A data entry is any varaible, constant, key or cell in the projects.
 * __Data Sources__: Data sources are sources of data entries: arrays, tables, documents, items & relation (an Item and a Relation are components that process data elements of tables and documents under a unified definition to create a single query language that can access both types of data, allowing for integration and fast transitions from [relational](https://en.wikipedia.org/wiki/Relational_database) to [document](https://en.wikipedia.org/wiki/Document-oriented_database) models. We will review the Item and Relation components in this document).
 * __Procedural Connections__: A connection between data entries, sources and other procedural connection outputs with processing elements (e.g. functions) - each procedural connection provides an output in run-time. This defintion is used to create the procedural diagrams defining the authorizations and other processes that can be attached to system flow during run-time using the Task and Process components which will be discussed later in this document.

## Components
<div align="center">
<img width="50%" src="https://gitlab.com/lesscomplexity/apishift/-/raw/master/images/Architecture.png">
</div>

The diagram above shows the different classifications each component can  belong to. The purpose of these classification is to create an abstraction above the architectural components that can, in theory, classify any type of API or server components into these definitions for providing a scalable and modular system to build any type of API/Server. These classification are built around the idea that anyone can define & add it's own components, and classify them according to this model. Then the base components are created with definitions and connections that abide this classification, and the ability to expand their functionallity and architecture by combining other components in the same classification, to fit the desired architecture of any API/server the developer wishes to make.

### Base Components
The system defines 3 main base concepts that are said to make a base for a whole API/Back-End, and provides components as follows:
1. __Core__: The basic components that hold and process data elements that define the configurations of the system. For example credentials for connecting to the main database, and other hardcoded metadata. Practically the core file is defined during system installation. These components are connected to other components of the system and are used for passing configurations between components.
    * [Core Configurations Class](machine/core/Configurations.php)
2. __Database__: The components that manage and communicate with the database to access the long-term memory from code and translate between different database models.
    * [Data Structure Manager & Translator Class](machine/core/DataModelManager.php)
    * [Item Class](machine/core/Item.php)
    * [Relation Class](machine/core/Relation.php)
    * [PDO Objects Collection Class](machine/core/DataModelManager.php)
3. __Session__: The components that manage the data elements and data strcuture and how they change in each session. Practically, in PHP terms, each session at any point in run-time has PHP array (key-value) defining the structure and data of the session stored in the `$_SESSION` variable, which our system refers to as the current session state.
    * [Session State Management Class](machine/core/SessionState.php)

### Mid-Level Components
The system also defines 3 more concepts that are connected to the base concept to build up the logic, restrictions and analysis of the API, and as follows, define modular and scalable components to handle it:
1. __Logic/Models__: All the components that make use of the session, database and core of the API to make the requests complete. in a way that componets who handle process requests (the controllers, which will be reviewed later) connect to, to complete their operation. All the these components are stored in the [models folder](machine/models), and also made viable by creating procedures using the system's UI and attaching them using triggers controllers, models or the lifecycle of the API.
    * [Procedure Management & Placement Class](machine/core/Task.php), Each task is made from different processes.
    * [Processes Management Class](machine/core/Process.php), Each process in a handler of procedural connections, that combines them to make a run-time process.
2. __Access__: Components that are attached in run-time to each request for validating the procedure or the data that a client wants to access. The Access management tools are based on Tasks & Processes and complete autorizations using the session state which main purpose is to store data that should indicate who is the client.
    * [Authorization Class](machine/core/Authorizer.php)
3. __Analysis__: Components that are connected to other components of the system to accumulate usage data on the different components and the data that is transferred in the system to illustrate a analytic image of the system in terms of performance, access and usage.

Around all of these definitions, the engine defines __Controllers__ as the combining part of the API. The controllers are components that contain all the possible requests that can be made to an API. Each controller makes use of logic, and is surrounded by access and analysis methods to complete the full features of the API.

## Session management
Each API/server usually needs different types of sessions. One session can represent a regular user on your application and another can represent a premium user, each type of session has different permissions in your system - some can access a certain function/data and others don't. APIShift allows you to define different session states easily and then assign access rules by these states. The classes that manage the session options are the [core SessionState](machine/core/SessionState.php) and the [controller SessionState](machine/controller/SessionState.php) which allows for changing and managing the session through API requests.

The core SessionState contains the logic and functions that manage the session states, their updates, authorization and communication with the database. The controller SessionState provides a set of functions that a user can use to manipulate the session state - for example change the session on request and more. The controller uses the core object to make these requests come to life. Each session state has a state structure, value and children:

 * __Structure__: Keys and nested keys that make up the session objects.
 * __Values__: Indicator where to take the values from to fill in the structure - is it from database or the data provided in the request? your choice.
 * __Children__: Children states are sub-states available on a certain state - they inherit and extend the structure and values of the parent state and use the same authorization process but with additional options or restrictions as you chose. For example a user session state can have a premium sub-session state that applies to premium users and provides access to more features in your application.

To add, modify and remove session states visit the "Session" tab in the control panel.

## Database management
This system is configurable from the control panel and comes to life in your code. The system defines the database structure using an Object + Graph model and translates this model to the relational and other NoSQL moedls (Which makes it both an [ORM](https://en.wikipedia.org/wiki/Object-relational_mapping) & [ODM](https://www.quora.com/What-is-Object-Document-Mapping)). Each entity\object is refered to as an Item (which not only represents a single table, but can reference multiple tables) and each connection, is refered to as a Relation - which in itself acts as an Item (Allowing for relations between relations). It is translated into the relational model - SQL, in future versions also to different NoSQL models for increased integration.

The system provides a graphical framework for constructing data in the database and managing access to the data. The [DataModelManager](machine/core/DataModelManager.php), [Item](machine/core/Item.php) & [Relation](machine/core/Relation.php) serve as the components that work with the graphical framework and translate it to the database's query language for you.

In later versions, you will be able to save your data on different DB servers, and APIShift will manage it for you - acting as a data warehouse. To add, modify and remove long-term data in your application visit the "Database" tab in the control panel.

### UI Graph Components
When working with database components of the APIShift, you create `Canvases`, where each canvas is a visual representation of database elements and how they are related & constructed. The system uses these terms/components:
 * __Item__: An abstract components that is presented as a collection of keys and values, that represent data elements stired in the DB.
 * __Relation__: A relation is an item that makes and abstract connection between 2 or more items. Since a relation is also an item you can make relations between relations - this is what makes the terminology of the engine as a combination between [graph model semantics](https://en.wikipedia.org/wiki/Graph_database), [object model semantics](https://en.wikipedia.org/wiki/Object_model) and an [entity-relationship model](https://en.wikipedia.org/wiki/Entity%E2%80%93relationship_model). Notice that I wrote 'abstract connection' as a relation doesn't store or address primary or foreign keys, this frees the system from normalizing the database only in one way, and makes it easier to translate between different data models. Each relation is one of those types:
    * *One-To-One*: For each instance of the relation, there can be no more than one instance for each of the parent item/s and related item/s (as you can see a relation can be derived from more that one item, referenced as parent item/s, to any other more than one item, references as related item/s).
    * *One-To-Many*: For each instance of the relation, there can be no more than one instance of parent items but as many related item instances as you like.
    * *Many-To-Many*: For each instance of the relation, there can as many parent and related items instances as you like.
 * __Group__: Items & Relations can be grouped together - grouping abstracts items and relations as a single elements which helps developers create relations between multiple items in a single connection, it is made for better user experience. Practically, when a relation is pointing to a group or from a group, the table representing the relation will have a `from_type` and `to_type` respectively, which are foreign keys related to a table holding the types of items in the group.
 * __Type__: Each Item & Relation can have types - for example the users item can be of type admin, premium or regular - this feature is also for better user experience, as developers can view and manage types in system queries, and even relate only specific types of items, which offers more flexibility.

These kind of defintions and components allow us to keep a single query language to access, cunstruct and normalize data elements in a database of any type (SQL and NoSQL structures like mongodb, graphQL and more).

## Procedure Management
More will be added later

# Project Structure
Here we will review the filesystem structure of the APIShift framework before getting into specifics.

## Back-End
The back-end of the system is in the [machine folder](machine/) where you can find 4 different folders:
 * __[Core folder](machine/core)__: The folder containing all the core files of the system. The core files are classes that make up the main workflow of the system and contain methods to work with the system's main features. Any new core system will be integrated into the existing file or as a new file. Everything in the core folder should be under the namespace `APIShift\Core`.
 * __[Models folder](machine/models)__: Contains any classes that are used to make logical and database specific operations outside the scope of the system - made for users of the system. If you are contributing then you will probably touch every folder besides this one, unless you are adding features for managing extensions or helper functions, as the extensions management class and helper class exist in this folder. Everything in the models folder should be under the namespace `APIShift\Models`.
 * __[Controllers folder](machine/controllers)__: The controllers holding the methods that define the requests that can be made to system as the [readme](README.md) explains in the usage section. Each controller method validates the request and then walks through the necessary models and core files to complete the request. The authorizer handles the authorization and access rules for user-made methods - but if you are making methods for the control panel and developers of the system make sure to use the `Authorizer::authorizeState()` function which exists if the user is not in admin state - which is set only of the developer logs in to the framework. Everything in the controlelrs folder should be under the namespace `APIShift\Controllers`.
 * __[Data folder](machine/data)__: Data files outside of the scope of PHP scope - ini and sql files that load and install the system and other helpful files for configurations and other stuff.

And there are 2 main files in the back-end:
 * **[APIShift](machine/APIShift.php)**: A simple file that loads the autoloader of the system - which knows how to interpret the framework's namespaces with ease, loads the session and starts the main connection with the database.
 * **[API](machine/API.php)**: This files integrated the API workflows for each request, it first calls the APIShift file to load the system, then it validates the request, calls the authorizer to authorize the request, and runs the desired controller & method - the only way to make API requests to any controller should be only from this file, as it integrates the authorization process. If you build your views from PHP files than you need to use only the APIShift file as explained in the [readme](README.md)'s usage section.

## Control panel UI
The second most important structure to understand for contributers is the UI of the control panel which sits in the [control folder](control/). The control panel has a [UI folder](control/UI/) that contains all the UI components and an [index file](control/index.html) that integrates them together to make the single-page application of the control panel. The UI folder contains 4 sub-folder:

 * __[Components](control/UI/components)__: Components that help pages be complete, or other mixins to store repeating vue function and components.
 * __[Pages](control/UI/pages)__: Vue pages. I don't think further explanation is necessary.
 * __[Scripts](control/UI/scripts)__: Scripts that contain helping functions and classes - mainly the [APIShift JS library](control/UI/scripts/APIShift.js) is stored there to help us communicate and stay with the same standard and data as the back-end.
 * __[Styles](control/UI/style)__: All the css and other styling files of the control panel.
