#[PressRoom](http://press-room.io/) for Wordpress

>freedom for the modern storyteller

![](PR-github.png?raw=true "PressRoom for Wordpress")

**Requires at least:** 3.8    
**Tested up to:** 4.0    
**Stable tag:** 1.0    
**License:** GPL v2   

## What is PressRoom?
PressRoom aims to turn WordPress into a multiple channel publishing platform. It is being developed around a vision for digital publishing that we see growing and struggling with the available technologies. 

Everything in PressRoom is structured around the concept of **Editorial Projects**; they are containers for an unlimited number of **Editions**, which are containers for any kind of content that you can already add and manage in WordPress. 

An **Edition** can be exported at the same time to an iOS client based on the [Baker Framework ](https://github.com/bakerframework/baker) and to the web as standalone microsite. 

**PressRoom** is the perfect tool to embrace a sub-compact publishing model, but you’re not restriced to any kind of content structure since everything is being build to be flexible and highly customizable. Suitable for books, magazines, newspapers or just to manage your static websites.  

##Installation

1. [Download the package](https://github.com/thePrintLabs/pressroom/archive/master.zip)
2. Unzip and rename the folder to "pressroom"
3. Extract or upload it to your ```/wp-content/plugins/``` directory, then activate
4. Set permalinks to ```/%postname%/```
5. Follow the next steps

#Initial Configuration
1. Configure the basic settings
2. Create your first **Editorial Project**
3. Connect your Baker Framework iOS App to your editorial project's **endpoint**
4. Create an **Edition**, review and publish ```;)```

## Basic settings

*[screenshot placeholder]*

- **Default theme:** a global setting for the default theme to be used for new *Editorial Projects* and *Editions*. It could be overwritten at *Editorial Project* or *Edition* level.
- **Max edition number:** this is the maximum number of *Editions* (issues) in the Baker ```shelf.json``` Baker clients will read the ```shelf.json``` to find available *Editions* (issues) and download related meta as cover image, price tag etc. 
- **Flush themes cache:** refresh cache when you add new themes or new templates inside your existing theme.
- **Connected custom post types:** Defines which custom post types are allowed to be included into an *Edition*.

## Editorial Project settings
Each Editorial Project has its own settings and a unique endpoint for Baker clients. Basically this means that a single PressRoom powered WordPress install could manage an unlimited number of *Editorial Projects*, and thus an unlimited number of Baker based Apps.   

*[screenshot placeholder]*

Each editorial project has 4 to 6 sections that need configuration:

- **Basic:** the name and slug of the editorial project.
- **Visualization / Behaviour:** these are the ```book.json``` settings that are allowed on Baker. PressRoom supports all the available ```book.json``` parameters. For a comprehensive guide about the topic please head over [Book.json Baker extension parameters](https://github.com/bakerframework/baker/wiki/Book.json-Baker-extension-parameters) on the official Baker Framework repository. 
- **TOC:** ```book.json``` settings for the table of contents. More info at [Adding an Index Bar to your publication](https://github.com/bakerframework/baker/wiki/Adding-an-Index-Bar-to-your-publication)
- **Push Notifications:** Insert your [Parse](https://parse.com/) or [Urban Airship](http://urbanairship.com/) api credentials. 

## Connecting Baker Framework Apps to PressRoom
After you've created your first **Editorial Project** an endpoint for your Baker client will be generated. You find it under the ```shelf.json``` column in the Editorial Projects list view. Add your endpoint to the ```settings.plist``` file in your Baker client Xcode project. Pretty straightforward.    
Some background info available at [https://github.com/bakerframework/baker/issues/354](https://github.com/bakerframework/baker/issues/354)

##Edition
You can create a new *Edition* under the 'Editions' admin menu item and proceed customizing the basic settings. 

*[screenshot placeholder]*

- ###Flatplan 
When you edit a post, page or allowed custom post type you are able to select one or more *Edition* for the content item to be part of. You could even create new *Editions* from the same meta box. Items belonging to an *Edition* are listed under the *Edition* **flatplan**.     
Items in the **flatplan** can be visible or hidden. Hidden items will not be include in the final package. Items can also be dragged to change their final order. 

- ###Themes
PressRoom comes pre-bundled with an initial theme to let you start quickly. You could use it it as starting point to learn how PressRoom themes works and to start developing and add your own. Themes added to the ```/pressroom/themes/``` folder will show up automatically.    
For each *Edition* you are able to define a custom theme. Under each theme you are able to define an unlimited number of template files. Next to each content item in the **flatplan** you could select which template file to use. 

- ###Preview
Inside the *Edition* edit page and *Editions* list view, you are able perform a complete web preview of the overall edition. 

- ###Publish
Each Edition must be part of an Editorial Project in order to be published. When an *Edition* is ready and checked with the web preview, the **publish button** will package all the content items, media and template assets and create a complete ```.hpub``` file. At the same time the related ```shelf.json``` gets  updated on the parent *Editorial Project* endpoint.
Free Newsstand subscriptions can be created on iTunes Connect to support Newsstand Apps with free content and automatic updates.

## How to get support
Open an issue [here on GitHub](https://github.com/thePrintLabs/pressroom/issues) or head over [http://discourse.press-room.io](http://discourse.press-room.io) for general usage question.

##Roadmap

- web export via ftp, sftp, local filesystem
- more themes
- extensive documentation

##Contributing
Contributions are always welcomed to further improve this plugin:

- Fork it
- Create your feature branch (git checkout -b my-new-feature)
- Commit your changes (git commit -am 'Add some feature')
- Push to the branch (git push origin my-new-feature)
- Create new Pull Request

##Licence
Each part of PressRoom is open source and licensed under GPL v2.    
**License URI:** [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)    
© 2014 [thePrintLabs Ltd.](http://theprintlabs.com)
