import * as React from 'react';
import { observer } from 'mobx-react';

import Spinner from './Spinner';

import './ProgressBar.less';

interface ProgressBarProps {
  progress: number;
}

const ProgressBar: React.FunctionComponent<ProgressBarProps> = ({ progress }) => {
  const isVisible = progress < 100;

  const barStyles = {
    transform: `translate3d(${progress - 100}%,0px,0px)`,
    display: (isVisible ? 'inline-block' : 'none'),
  };

  return (
    <div className='ProgressBar'>
      <div className='ProgressBar-bar' style={barStyles} />
      {isVisible &&
        <Spinner />
      }
    </div>
  );
};

export default observer(ProgressBar);
