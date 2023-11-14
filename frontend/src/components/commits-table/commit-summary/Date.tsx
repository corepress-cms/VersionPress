import * as React from 'react';
import { observer } from 'mobx-react';
import { format, formatDistanceToNow } from 'date-fns';
import { parse } from '../../search/modifiers/date/adapter';

interface DateProps {
  date: string;
}

const Date: React.FunctionComponent<DateProps> = ({ date }) => {
  const dateObj = parse(date);
  return (
    <td className='column-date' title={format(dateObj,'LLL')}>
      {formatDistanceToNow(dateObj)}
    </td>
  );
};

export default observer(Date);
