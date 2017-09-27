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
function Starfield(starfieldContainer, playerContainer, loadingContainer) {
    this.init(starfieldContainer, playerContainer, loadingContainer);
}


/**
 * @chapter
 * PROPERTIES
 * -------------------------------------------------------------------------
 */
Starfield.prototype.loadingFlag = true;

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
        
        for (var i = 0; i < data.players.length; i++) {
            
            $('<div/>', {
                'class': 'player-entry'
            })
            .append(i.toString(10) + '. ' + data.players[i].username)
            .click((function(index) {
                return function() {
                    alert('open profile of player with id: ' + data.players[index].userId);
                }
            })(i))
            .hover((function(index) {
                return function() {
                    instance.loadUserStarsStaged(data.players[index].userId);
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
    
    instance.stagedUser = userId;
    
    if (typeof instance.starsLoaded == 'undefined')
        instance.starsLoaded = true;
    
    if (typeof instance.starsStaged == 'undefined')
        instance.starsStaged = false;
    
    // No user staged yet?
    if (!instance.starsStaged) {
        
        instance.starsStaged = true;
        
        setTimeout(function() {
            
            // Not currently loading...
            if (instance.starsLoaded) {
                
                instance.starsLoaded = false;
                
                // Show loading container
                if (instance.loadingFlag)
                    instance.$loading.fadeIn(200);
                
                // Load user stars
                instance.loadUserStars(instance.stagedUser, function() {
                    
                    // Hide loading container
                    if (instance.loadingFlag)
                        instance.$loading.fadeOut(200);
                    
                    instance.starsLoaded = true;
                    instance.starsStaged = false;
                    
                });
                
            } else {
                
                instance.starsStaged = false;
                instance.loadUserStarsStaged(instance.stagedUser);
                
            }
            
        }, 100);
        
    }
    
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
    if (instance.loadingFlag)
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
                level = 2;

            else if (data.stars[i].level >= 3)
                level = 1;

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
                alert('Load transcription... | Star id: ' + this.data('i').toString());
                $.post('starfield', { task: 'transcribe', star: this.data('i') }, function(data) {

                    if (data.error != 'none') {
                        // Error occured
                        return;
                    }

                    // Redirect
                    window.location.replace('transcription');

                }, 'json');

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
 * INITIALIZATION
 * -------------------------------------------------------------------------
 */

/**
 * Initializes the Starfield object.
 * @private
 */
Starfield.prototype.init = function(starfieldContainer, playerContainer, loadingContainer) {

    if (!document.getElementById(starfieldContainer))
        throw new Error('[SaveYourLanguage] Starfield: no game container found!');

    if (!document.getElementById(playerContainer))
        throw new Error('[SaveYourLanguage] Starfield: no player container (list) found!');

    // This instance
    var instance = this;

    if (!document.getElementById(loadingContainer))
        instance.loadingFlag = false;
    else
        instance.$loading = $('#' + loadingContainer);

    // Save player list selector
    instance.$players = $('#' + playerContainer);

    // View selectors
    instance.$dialog = $('<div/>', {
        'class': 'full-size',
        'style': 'display: none; ' +
                 'background-color: rgba(20, 20, 20, 0.9);' +
                 'z-index: 1000;'
    }).appendTo($('#' + starfieldContainer).parent());

    instance.initSize = {};
    instance.initSize.width = $('#' + starfieldContainer).width();
    instance.initSize.height = $('#' + starfieldContainer).height();

    // Setup Raphael
    instance.paper = new Raphael(starfieldContainer);
    instance.paper.setViewBox(0, 1, instance.initSize.width, instance.initSize.height, true);
    instance.paper.setSize('100%', '100%');

    // Proxy all object functions
    $.proxy(instance.loadPlayerList, instance);
    $.proxy(instance.loadUserStarsStaged, instance);
    $.proxy(instance.loadUserStars, instance);
    $.proxy(instance.resetStarfield, instance);
    $.proxy(instance.pixelToPrecent, instance);

    // Load starfield
    instance.resetStarfield(function() {

        // Load this users star sequence
        instance.loadUserStars(0, function() {

            // Load player list
            instance.loadPlayerList();
            
            // Hide loading container
            if (instance.loadingFlag)
                instance.$loading.fadeOut(200);

        });

    });

};
