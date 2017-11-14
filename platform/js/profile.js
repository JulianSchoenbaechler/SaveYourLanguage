/**
 * Profile
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
 * Gateway object to the Constellation class.
 * @constructor
 */
function Constellation(starfield, leftArrow, rightArrow, title) {
    this.init(starfield, leftArrow, rightArrow, title);
}


/**
 * @chapter
 * PROPERTIES
 * -------------------------------------------------------------------------
 */
Constellation.prototype.starImages = [
    'platform/img/star-small.png',
    'platform/img/star-middle.png',
    'platform/img/star-big.png'
];

// Raphael canvas
Constellation.prototype.paper = undefined;

// Raphael sets
Constellation.prototype.starSet = undefined;


/**
 * @chapter
 * CONSTANTS
 * -------------------------------------------------------------------------
 */
Constellation.STAR_SIZE = 64;


/**
 * @chapter
 * PUBLIC FUNCTIONS
 * -------------------------------------------------------------------------
 */

/**
 * Loading and render stars and connections from a specific user.
 * @param {Number} userId - User identifier.
 * @param {Function} callback - A callback function indicating finished operation.
 */
Constellation.prototype.loadUserStars = function(callback) {
    
    var instance = this;
    
    if (typeof instance.uid != 'number')
        return;

    // Container variable holding coordinates
    var tempCoordinates;

    $.post('starfield', { task: 'user', user: instance.uid }, function(data) {
        console.log(data);
        if (typeof data.error != 'undefined') {
            // Error occured
            return;
        }

        // Remove all elements from the star set and clear those
        if (typeof instance.starSet != 'undefined') {
            instance.starSet.remove();
            instance.starSet.clear();
        } else {
            instance.starSet = instance.paper.set();
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
            
            // Use another image for last connected star
            var star = instance.paper.image(instance.starImages[0],
                                            tempCoordinates.x - (Constellation.STAR_SIZE / 2),
                                            tempCoordinates.y - (Constellation.STAR_SIZE / 2),
                                            Constellation.STAR_SIZE,
                                            Constellation.STAR_SIZE);
            
            instance.starSet.push(star);
        }
            
        // Draw path onto canvas
        var path = instance.paper.path(pathString);
        path.attr({
            'stroke': '#D8F1FF',
            'stroke-width': 3
        });

        // Add path to set
        instance.starSet.push(path);

        // Fire callback if one is defined
        if (callback)
            callback();

    }, 'json').fail(function(data) { console.log(data); });

};

/**
 * Converts a coordinate in percent to pixels according to the size of the starfield.
 * @param {Number} x - The X coordinate in %.
 * @param {Number} y - The Y coordinate in %.
 * @returns {Object} A coordinate object with the calculated values in pixels.
 */
Constellation.prototype.percentToPixel = function(x, y) {

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
 * Dissolves specific URL GET parameters
 * @param {String} name - The parameter to search for.
 */
Constellation.prototype.getUrlParameter = function(name) {

    var result = null;
    var tmp = [];
    
    window.location.search
        .substr(1)
        .split('&')
        .forEach(function(item) {
            tmp = item.split('=');
            
            if(tmp[0] === name)
                result = decodeURIComponent(tmp[1]);
            
        });
        
    return result;
    
};

/**
 * Dissolves specific URL GET parameters
 * @param {String} name - The parameter to search for.
 * @param {Function} callback - The callback when parameter found.
 */
Constellation.prototype.getUrlParameter = function(name, callback) {

    var result = null;
    var tmp = [];
    
    window.location.search
        .substr(1)
        .split('&')
        .forEach(function(item) {
            tmp = item.split('=');
            
            if (tmp[0] === name) {
                result = decodeURIComponent(tmp[1]);
                
                if (typeof callback == 'function')
                    callback();
            }
            
        });
        
    return result;
    
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
Constellation.prototype.init = function(starfield, leftArrow, rightArrow, title) {

    if (!document.getElementById(starfield))
        throw new Error('[SaveYourLanguage] Profile: no field container found!');

    // This instance
    var instance = this;

    // Reference to arrow containers
    instance.$leftArrow = $('#' + leftArrow);
    instance.$rightArrow = $('#' + rightArrow);
    
    // Reference title text
    instance.$title = $('#' + title);
    
    // Get displayed user
    instance.uid = parseInt(instance.getUrlParameter('id'), 10) || 0;

    // Initialize size
    instance.initSize = {
        width: 768,
        height: 371
    };

    // Setup Raphael
    instance.paper = new Raphael(starfield);
    instance.paper.setViewBox(0, 1, instance.initSize.width, instance.initSize.height, true);
    instance.paper.setSize('100%', '100%');

    // Proxy all object functions
    $.proxy(instance.loadUserStars, instance);
    $.proxy(instance.pixelToPrecent, instance);
    
    // Load this users star sequence
    instance.loadUserStars(function() {
        // Callback
    });

};
