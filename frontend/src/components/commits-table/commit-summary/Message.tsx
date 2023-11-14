import * as React from 'react';
import { observer } from 'mobx-react';

import DetailsLevelButtons from './DetailsLevelButtons';
import MergeIcon from './MergeIcon';
import DetailsLevel from '../../../enums/DetailsLevel';

interface MessageProps {
  commit: Commit;
  detailsLevel: DetailsLevel;
  onDetailsLevelChange(detailsLevel: DetailsLevel): void;
}

const Message: React.FunctionComponent<MessageProps> = ({ commit, detailsLevel, onDetailsLevelChange }) => (
  <td className='column-message'>
    {commit.isMerge &&
      <MergeIcon />
    }
    {renderMessage(commit.message)}
    {detailsLevel !== DetailsLevel.None &&
      <DetailsLevelButtons
        detailsLevel={detailsLevel}
        onDetailsLevelChange={onDetailsLevelChange}
      />
    }
  </td>
);

function renderMessage(message: string): React.ReactNode {
  const messageChunks = /(.*)'(.*)'(.*)/.exec(message);

  if (!messageChunks || messageChunks.length !== 4) {
    return <span>{message}</span>;
  }

  return (
    <span>
      {messageChunks[1] !== '' && renderMessage(messageChunks[1])}
      {messageChunks[2] !== '' && <span className='identifier'>{messageChunks[2]}</span>}
      {messageChunks[3] !== '' && renderMessage(messageChunks[3])}
    </span>
  );
}

export default observer(Message);
