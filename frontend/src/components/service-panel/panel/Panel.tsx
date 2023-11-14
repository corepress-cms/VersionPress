import * as React from 'react';
import classNames from 'classnames';
import { observer } from 'mobx-react';

import Support from './Support';
import Warning from './Warning';

interface PanelProps {
  isVisible: boolean;
}

const Panel: React.FunctionComponent<PanelProps> = ({ isVisible }) => {
  const wrapperClassName = classNames({
    'ServicePanel-wrapper': true,
    'ServicePanel-wrapper--hide': !isVisible,
  });

  return (
    <div className={wrapperClassName}>
      <div className='ServicePanel welcome-panel'>
        <div className='ServicePanel-inner'>
          <Warning />
          <Support />
        </div>
      </div>
    </div>
  );
};

export default observer(Panel);
