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
function Starfield(starfieldContainer, loadingContainer) {
    this.init(starfieldContainer, loadingContainer);
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
 * Clears and reloads the starfield.
 */
Starfield.prototype.resetStarfield = function() {
    
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
            newStar.data('i', i + 1).click(function() { alert('Load transcription... | Star id: ' + this.data('i').toString()); });
            
            // Hover event handler(s)
            newStar.hover(function() {
                this.attr({ cursor: 'pointer' });
            }, function() {
                this.attr({ cursor: 'default' });
            });
            
            // Add new star to set
            instance.starSet.push(newStar);
            
        }
        
        // Disable loading screen
        if (instance.loadingFlag)
            instance.$loading.fadeOut(200);
        
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
Starfield.prototype.init = function(starfieldContainer, loadingContainer) {
    
    if (!document.getElementById(starfieldContainer))
        throw new Error('[SaveYourLanguage] Starfield: no game container found!');
    
    var instance = this;
    
    if (!document.getElementById(loadingContainer))
        instance.loadingFlag = false;
    else
        instance.$loading = $('#' + loadingContainer);
    
    instance.initSize = {};
    instance.initSize.width = $('#' + starfieldContainer).width();
    instance.initSize.height = $('#' + starfieldContainer).height();
    
    // Setup Raphael
    instance.paper = Raphael(starfieldContainer);
    instance.paper.setViewBox(0, 1, instance.initSize.width, instance.initSize.height, true);
    instance.paper.setSize('100%', '100%');
    
    // Proxy all object functions
    $.proxy(instance.resetStarfield, instance);
    $.proxy(instance.pixelToPrecent, instance);
    
    // Load starfield
    instance.resetStarfield();
    
};
