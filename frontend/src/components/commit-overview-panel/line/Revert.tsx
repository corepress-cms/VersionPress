/// <reference path='../../common/Commits.d.ts' />

import * as React from 'react';
import { observer } from 'mobx-react';
import {format, formatDistanceToNow} from 'date-fns';
import { parse } from '../../search/modifiers/date/adapter';

import { LineProps } from './Line';

const Revert: React.FunctionComponent<LineProps> = ({ changes }) => {
  const change = changes[0];
  const action = change.action;

  const commitDetails = change.tags['VP-Commit-Details'];
  const message = commitDetails['message'];

  if (action === 'rollback') {
    return (
      <span>
        {`The state is same as it was in "${message}"`}
      </span>
    );
  } else {
    const date = parse(change.tags['VP-Commit-Details']['date']);
    const dateRel = formatDistanceToNow(date);
    const dateAbs = format(date, 'LLL');

    return (
      <span>
        {`Reverted change "${message}" was made ${dateRel} (${dateAbs})`}
      </span>
    );
  }
};

export default observer(Revert);
