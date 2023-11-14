import * as React from 'react';
import { DayPicker } from 'react-day-picker';
import { isSameDay, add, sub } from "date-fns";
import { parse } from './adapter';

import ModifierComponent from '../ModifierComponent';

import 'react-day-picker/dist/style.css';

export default class DateComponent extends ModifierComponent<{}> {

  onUpClicked = () => {
    const { activeTokenIndex, adapter, token, onChangeTokenModel } = this.props;

    const date = token.value;
    const cursorLocationType = this.getCursorLocationType();

    if (adapter.isValueValid(date) && date && cursorLocationType) {
      const newDate = add(parse(date), {[cursorLocationType]: 1});
      onChangeTokenModel(activeTokenIndex, newDate, false);
    }
  }

  onDownClicked = () => {
    const { activeTokenIndex, adapter, token, onChangeTokenModel } = this.props;

    const date = token.value;
    const cursorLocationType = this.getCursorLocationType();

    if (adapter.isValueValid(date) && date && cursorLocationType) {
      const newDate = sub(parse(date), {[cursorLocationType]: 1});
      onChangeTokenModel(activeTokenIndex, newDate, false);
    }
  }

  onSelect = () => {
    const { activeTokenIndex, token, onChangeTokenModel } = this.props;
    const { value } = token;

    if (!value) {
      return false;
    }

    onChangeTokenModel(activeTokenIndex, value, true);
    return true;
  }

  onDayClick = (e: any, day: any) => {
    const { activeTokenIndex, onChangeTokenModel } = this.props;
    onChangeTokenModel(activeTokenIndex, parse(day), true);
  }

  getCursorLocationType = () => {
    const { cursor, token } = this.props;
    const modifierLength = token.modifier.length;

    if (cursor >= modifierLength) {
      if (cursor < (modifierLength + 5)) {
        return 'years';
      } else if (cursor < (modifierLength + 8)) {
        return 'months';
      } else if (cursor < (modifierLength + 11)) {
        return 'days';
      }
    }
  }

  isDateValid(date: Date) {
    return Object.prototype.toString.call(date) === '[object Date]'
           && !isNaN(date.getTime());
  }

  render() {
    const { token } = this.props;
    const selectedDay = new Date(token.value);
    const isValid = this.isDateValid(selectedDay);

    return (
      <div onMouseDown={e => e.preventDefault()} className='Search-hintMenu-container'>
        {isValid
          ? <DayPicker
              defaultMonth={selectedDay}
              onDayClick={this.onDayClick}
              selected={day => isSameDay(selectedDay, day)}
            />
          : <DayPicker
              onDayClick={this.onDayClick}
            />
        }
      </div>
    );
  }

}
