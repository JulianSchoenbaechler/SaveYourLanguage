/**
 * Starfield
 * @copyright 2017 University of the Arts, Zurich
 * @version 0.1.0
 * @author Julian Schoenbaechler
 *
 * @external jQuery
 * @see {@link http://api.jquery.com/jQuery/}
 *
 * @external Raphael
 * @see {@link https://dmitrybaranovskiy.github.io/raphael/}
 */

/**
 * Gateway object to the Starfield class.
 * @constructor
 */
function Starfield(starfieldContainer, playerContainer) {
    this.init(starfieldContainer, playerContainer);
}


/**
 * @chapter
 * PROPERTIES
 * -------------------------------------------------------------------------
 */
Starfield.prototype.blockStagedLoading = false;
Starfield.prototype.stagedStarId = 0;
Starfield.prototype.stagedConnection = true;

Starfield.prototype.starImages = [
    'platform/img/star-small.png',
    'platform/img/star-middle.png',
    'platform/img/star-big.png'
];

// Raphael canvas
Starfield.prototype.paper = undefined;

// Raphael sets
Starfield.prototype.starSet = undefined;
Starfield.prototype.pathSet = undefined;
Starfield.prototype.mainPath = undefined;


/**
 * @chapter
 * CONSTANTS
 * -------------------------------------------------------------------------
 */
Starfield.STAR_SIZE = 16;


/**
 * @chapter
 * PUBLIC FUNCTIONS
 * -------------------------------------------------------------------------
 */

/**
 * Loading playerlist. Player with most transcriptions made first.
 */
Starfield.prototype.loadPlayerList = function() {

    var instance = this;
    
    $.post('starfield', { task: 'players' }, function(data) {

        if (typeof data.error != 'undefined') {
            // Error occured
            return;
        }
        
        for (var i = 0; i < data.bestPlayers.length; i++) {
            
            $('<div/>', {
                'class': 'player-entry'
            })
            .append(i.toString(10) + '. ' + data.bestPlayers[i].username)
            .click((function(index) {
                return function() {
                    alert('Load profile user: ' + index);
                }
            })(i))
            .hover((function(index) {
                return function() {
                    instance.loadUserStarsStaged(data.bestPlayers[index].userId);
                }
            })(i), (function(index) {
                return function() {
                    instance.loadUserStarsStaged(0);
                }
            })(i))
            .appendTo(instance.$players);
            
        }

    }, 'json');

}

/**
 * Staging user identifiers from which stars should be loaded. This function prevents
 * from interrupting the stars loading process.
 * @param {Number} userId - User identifier.
 */
Starfield.prototype.loadUserStarsStaged = function(userId) {
    
    if (typeof userId != 'number')
        return;
        
    var instance = this;

    if (instance.blockStagedLoading)
        return;
    
    instance.stagedUser = userId;
    
    if (typeof instance.starsLoaded == 'undefined')
        instance.starsLoaded = true;
    
    // Load after a small delay
    setTimeout(function() {
        
        // Not currently loading...
        if (instance.starsLoaded) {
            
            instance.starsLoaded = false;
            
            // Show loading container
            instance.$loading.fadeIn(200);
            
            // Load user stars
            instance.loadUserStars(instance.stagedUser, function() {
                
                // Hide loading container
                instance.$loading.fadeOut(200);
                
                instance.starsLoaded = true;
                
            });
            
        }
        
    }, 100);
    
}

/**
 * Loading and render stars and connections from a specific user.
 * @param {Number} userId - User identifier.
 * @param {Function} callback - A callback function indicating finished operation.
 */
Starfield.prototype.loadUserStars = function(userId, callback) {

    if (typeof userId != 'number')
        return;

    var instance = this;

    // Container variable holding coordinates
    var tempCoordinates;

    $.post('starfield', { task: 'user', user: userId }, function(data) {

        if (typeof data.error != 'undefined') {
            // Error occured
            return;
        }

        // Remove all elements from the path set and clear those
        if (typeof instance.pathSet != 'undefined') {
            instance.pathSet.remove();
            instance.pathSet.clear();
        } else {
            instance.pathSet = instance.paper.set();
        }

        // SVG path string
        var pathString = '';

        // Iterate through path coordinates
        for (var i = 0; i < data.path.length; i++) {

            // Get coordinates from percentages
            tempCoordinates = instance.percentToPixel(data.path[i].x, data.path[i].y);

            // Firs path point?
            if ((i === 0) || !data.path[i].connected)
                pathString += 'M' + tempCoordinates.x.toString() + ',' + tempCoordinates.y.toString();
            else
                pathString += 'L' + tempCoordinates.x.toString() + ',' + tempCoordinates.y.toString();

        }

        // This user?
        if (userId == 0) {
            
            if (typeof instance.mainPath != 'undefined')
                instance.mainPath.remove();
            
            // Draw main path onto canvas
            instance.mainPath = instance.paper.path(pathString);
            instance.mainPath.attr({
                'stroke': '#D8F1FF',
                'stroke-width': 3,
                'opacity': 0.2
            });
            
        }
            
        // Draw path onto canvas
        var path = instance.paper.path(pathString);
        path.attr({
            'stroke': '#D8F1FF',
            'stroke-width': 3
        });

        // Add path to set
        instance.pathSet.push(path);

        // Fire callback if one is defined
        if (callback)
            callback();

    }, 'json');

};

/**
 * Clears and reloads the starfield.
 * @param {Function} callback - A callback function indicating finished operation.
 */
Starfield.prototype.resetStarfield = function(callback) {

    var instance = this;

    // Enable loading screen
    instance.$loading.fadeIn(200);

    // Remove all elements from the sets and clear those
    if (typeof instance.pathSet != 'undefined') {
        instance.pathSet.remove();
        instance.pathSet.clear();
    } else {
        instance.pathSet = instance.paper.set();
    }

    if (typeof instance.starSet != 'undefined') {
        instance.starSet.remove();
        instance.starSet.clear();
    } else {
        instance.starSet = instance.paper.set();
    }


    var tempCoordinates, level;

    // Ajax request -> load starfield
    $.post('starfield', { task: 'load' }, function(data) {

        if (typeof data.error != 'undefined') {
            // Error occured
            return;
        }

        // Draw stars
        for (var i = 0; i < data.stars.length; i++) {

            tempCoordinates = instance.percentToPixel(data.stars[i].x, data.stars[i].y);

            if (data.stars[i].level >= 5)
                level = 4;

            else if(data.stars[i].level > 0)
                level = data.stars[i].level - 1;
            
            else
                level = 0;
                

            // Draw new star
            var newStar = instance.paper.image(instance.starImages[level],
                                               tempCoordinates.x - (Starfield.STAR_SIZE / 2),
                                               tempCoordinates.y - (Starfield.STAR_SIZE / 2),
                                               Starfield.STAR_SIZE,
                                               Starfield.STAR_SIZE);

            // Click event handler
            newStar.data('i', i + 1).click(function() {

                // Stage star id for transcription
                instance.$prompt.fadeIn(200);
                instance.blockStagedLoading = true;
                instance.stagedStarId = this.data('i');
                /*
                alert('Load transcription... | Star id: ' + this.data('i').toString());
                $.post('starfield', { task: 'transcribe', star: this.data('i') }, function(data) {

                    if (data.error != 'none') {
                        // Error occured
                        return;
                    }

                    // Redirect
                    window.location.replace('transcription');

                }, 'json');
                */

            });

            // Hover event handler(s)
            newStar.hover(function() {
                this.attr({ cursor: 'pointer' });
            }, function() {
                this.attr({ cursor: 'default' });
            });

            // Add new star to set
            instance.starSet.push(newStar);

        }

        // Fire callback if one is defined
        if (callback)
            callback();

    }, 'json');

};

/**
 * Converts a coordinate in percent to pixels according to the size of the starfield.
 * @param {Number} x - The X coordinate in %.
 * @param {Number} y - The Y coordinate in %.
 * @returns {Object} A coordinate object with the calculated values in pixels.
 */
Starfield.prototype.percentToPixel = function(x, y) {

    var instance = this;

    return {
        x: (instance.initSize.width / 100) * x,
        y: (instance.initSize.height / 100) * y
    };

};


/**
 * @chapter
 * PRIVATE FUNCTIONS
 * -------------------------------------------------------------------------
 */
 
/**
 * Click handler for confirm prompt -> 'connect star'.
 * This function must be bound to an onClick callback.
 * @private
 */
Starfield.prototype.connectHandler = function() {
    
    var instance = this;
    
    instance.stagedConnection = true;
    instance.$prompt.fadeOut(200);
    instance.$transcription.fadeIn(200);
    
};

/**
 * Click handler for confirm prompt -> 'connect star'.
 * This function must be bound to an onClick callback.
 * @private
 */
Starfield.prototype.disconnectHandler = function() {
    
    var instance = this;
    
    instance.stagedConnection = false;
    instance.$prompt.fadeOut(200);
    instance.$transcription.fadeIn(200);
    
};

/**
 * Click handler for canceling transcription.
 * This function must be bound to an onClick callback.
 * @private
 */
Starfield.prototype.cancelTranscription = function() {
    
    var instance = this;
    
    instance.$transcription.fadeOut(200);
    
};

/**
 * Form submit handler for creating transcription.
 * This function must be bound to an onSubmit callback.
 * @private
 */
Starfield.prototype.createTranscription = function() {
    
    var instance = this;
    
    var value = instance.$transcription.find('#transcription-field').val();
    
    // Made transcription?
    if (value.length == 0)
        return;
    
    instance.$transcription.fadeOut(200);
    instance.$loading.fadeIn(200);
    
    // Ajax request -> try to create transcription
    $.post('transcription', {
        transcription: value,
        starId: instance.stagedStarId,
        connect: instance.stagedConnection ? 1 : 0
    }, function(data) {
        
        if (data.error != 'none') {
            console.log('[SaveYourLanguage] Error while saving transcription: ' + data.error);
            instance.$transcription.fadeIn(200);
            return;
        }
        
    }, 'json')
    .always(function() {
        
        instance.blockStagedLoading = false;
        instance.$loading.fadeOut(200);
        instance.loadUserStarsStaged(0);
        
    });
    
};


/**
 * @chapter
 * INITIALIZATION
 * -------------------------------------------------------------------------
 */

/**
 * Initializes the Starfield object.
 * @private
 */
Starfield.prototype.init = function(starfieldContainer, playerContainer) {

    if (!document.getElementById(starfieldContainer))
        throw new Error('[SaveYourLanguage] Starfield: no game container found!');

    if (!document.getElementById(playerContainer))
        throw new Error('[SaveYourLanguage] Starfield: no player container (list) found!');

    // This instance
    var instance = this;

    // Reference loading container
    instance.$loading = $('#' + starfieldContainer + ' #loading');
    
    // Reference prompt container
    instance.$prompt = $('#' + starfieldContainer + ' #prompt');
    
    // Reference transcription container
    instance.$transcription = $('#' + starfieldContainer + ' #transcription');

    // Save player list selector
    instance.$players = $('#' + playerContainer);

    // Initialize size
    instance.initSize = {};
    instance.initSize.width = 1200;//$('#' + starfieldContainer + ' #starfield').width();
    instance.initSize.height = 675;//$('#' + starfieldContainer + ' #starfield').height();

    // Setup Raphael
    instance.paper = new Raphael('starfield');
    instance.paper.setViewBox(0, 1, instance.initSize.width, instance.initSize.height, true);
    instance.paper.setSize('100%', '100%');

    // Proxy all object functions
    $.proxy(instance.loadPlayerList, instance);
    $.proxy(instance.loadUserStarsStaged, instance);
    $.proxy(instance.loadUserStars, instance);
    $.proxy(instance.resetStarfield, instance);
    $.proxy(instance.pixelToPrecent, instance);

    // Button click handlers for prompt / confirm dialog
    // And transcription
    instance.$prompt.find('#yes-button').click(
        $.proxy(instance.connectHandler, instance)
    );
    instance.$prompt.find('#no-button').click(
        $.proxy(instance.disconnectHandler, instance)
    );
    instance.$transcription.find('#cancel-button').click(
        $.proxy(instance.cancelTranscription, instance)
    );
    instance.$transcription.find('form').submit(
        $.proxy(instance.createTranscription, instance)
    );
    
    // Load starfield
    instance.resetStarfield(function() {

        // Load this users star sequence
        instance.loadUserStars(0, function() {

            // Load player list
            instance.loadPlayerList();
            
            // Hide loading container
            instance.$loading.fadeOut(200);

        });

    });

};
