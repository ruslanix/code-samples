const ratio = 0.618033988749895;
let hue   = Math.random();

/**
* HSV to RGB color conversion
*
* H runs from 0 to 360 degrees
* S and V run from 0 to 100
*
* Ported from the excellent java algorithm by Eugene Vishnevsky at:
* http://www.cs.rit.edu/~ncs/color/t_convert.html
*/
function hsvToRgb(h: number, s: number, v: number) {
  let r, g, b;

  // Make sure our arguments stay in-range
  h = Math.max(0, Math.min(360, h));
  s = Math.max(0, Math.min(100, s));
  v = Math.max(0, Math.min(100, v));

  // We accept saturation and value arguments from 0 to 100 because that's
  // how Photoshop represents those values. Internally, however, the
  // saturation and value are calculated from a range of 0 to 1. We make
  // That conversion here.
  s /= 100;
  v /= 100;

  if(s == 0) {
    // Achromatic (grey)
    r = g = b = v;
    return [
      Math.round(r * 255),
      Math.round(g * 255),
      Math.round(b * 255)
    ];
  }

  h /= 60; // sector 0 to 5
  const i = Math.floor(h);
  const f = h - i; // factorial part of h
  const p = v * (1 - s);
  const q = v * (1 - s * f);
  const t = v * (1 - s * (1 - f));

  switch(i) {
    case 0:
      r = v;
      g = t;
      b = p;
      break;

    case 1:
      r = q;
      g = v;
      b = p;
      break;

    case 2:
      r = p;
      g = v;
      b = t;
      break;

    case 3:
      r = p;
      g = q;
      b = v;
      break;

    case 4:
      r = t;
      g = p;
      b = v;
      break;

    default: // case 5:
      r = v;
      g = p;
      b = q;
  }

  return [
    Math.round(r * 255),
    Math.round(g * 255),
    Math.round(b * 255)
  ];
}

/**
 * Copy from
 * https://www.npmjs.com/package/random-color
 *
 * Returns [r, g, b] color
 *
 * @param saturation
 * @param value
 */
function randomColor(saturation?: number, value?: number) {

  hue += ratio;
  hue %= 1;

  if (typeof saturation !== 'number') {
    saturation = 0.5;
  }

  if (typeof value !== 'number') {
    value = 0.95;
  }

  return hsvToRgb(hue * 360, saturation * 100, value * 100);
};

export default randomColor;
