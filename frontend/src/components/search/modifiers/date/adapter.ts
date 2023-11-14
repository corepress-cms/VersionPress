/// <reference path='../../Search.d.ts' />
/// <reference path='../Adapter.d.ts' />

import { format, parse as _parse } from 'date-fns';

import { getMatch } from '../../utils/';

export const DATE_FORMAT = 'yyyy-MM-dd';
const DATE_FORMAT_REGEXP = /^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/;

class DateAdapter implements Adapter {

  constructor (config: SearchConfigItem) {}

  autoComplete = (token: Token) => {
    const { value } = token;

    const hints = this.getHints(token);

    if (hints.length && hints[0].indexOf(value) === 0) {
      return hints[0];
    }
  }

  getDefaultHint = () => {
    return format(Date.now(), DATE_FORMAT);
  }

  getHints = (token: Token) => {
    if (token) {
      const possibleValues = [format(Date.now(), DATE_FORMAT)];
      return getMatch(token.value, possibleValues);
    }
    return [];
  }

  isValueValid = (value: string) => {
    if (!DATE_FORMAT_REGEXP.test(value)) {
      return false;
    }
    try {
      parse(value);
      return true;
    } catch {
      return false;
    }
  }

  serialize = (date: any) => {
    if (!date) {
      return '';
    }
    if (typeof date === 'string') {
      if (this.isValueValid(date)) {
        return format(parse(date), DATE_FORMAT);
      }
      return date;
    }
    return format(date, DATE_FORMAT);
  }

  deserialize = (value: string) => {
    return '';
  }

}

export default DateAdapter;

export const parse = (value: string) => {
  try {
    let date = _parse(value, DATE_FORMAT, new Date());
    if (isNaN(date.getTime())) {
      throw new Error('Invalid date');
    }
    return date;
  } catch {
    return Date.parse(value);
  }
}
