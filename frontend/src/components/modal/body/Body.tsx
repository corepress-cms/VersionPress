import * as React from 'react';
import { observer } from 'mobx-react';

interface BodyProps {
  children?: React.ReactNode;
}

const Body: React.FunctionComponent<BodyProps> = ({ children }) => (
  <div className='Modal-body'>
    {children}
  </div>
);

export default observer(Body);
