/// <reference path='../../common/Diff.d.ts' />

import * as React from 'react';
import { observer } from 'mobx-react';

interface BinaryFileDiffProps {
  diff: Diff;
}

const BinaryFileDiff: React.FunctionComponent<BinaryFileDiffProps> = ({ diff }) => (
  <div className='binary-file-info'>
    {getMessage(diff)}
  </div>
);

function getMessage(diff: Diff) {
  if (diff.from === '/dev/null') {
    return 'Added binary file';
  } else if (diff.to === '/dev/null') {
    return 'Deleted binary file';
  } else {
    return 'Changed binary file';
  }
}

export default observer(BinaryFileDiff);
